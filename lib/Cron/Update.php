<?php

declare(strict_types=1);

namespace OCA\ScimClient\Cron;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Db\ScimEvent;
use OCA\ScimClient\Service\NetworkService;
use OCA\ScimClient\Service\ScimApiService;
use OCA\ScimClient\Service\ScimEventService;
use OCA\ScimClient\Service\ScimServerService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class Update extends TimedJob {

	private const ALLOWED_USER_ATTRIBUTES = [
		'active' => 'active',
		'displayName' => 'name.formatted',
		'eMailAddress' => 'emails.value',
	];

	public function __construct(
		ITimeFactory $time,
		private readonly NetworkService $networkService,
		private readonly ScimApiService $scimApiService,
		private readonly ScimEventService $scimEventService,
		private readonly ScimServerService $scimServerService,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);

		// Run every 5 minutes
		$this->setInterval(300);
	}

	protected function run($argument): void {
		$events = $this->scimEventService->getScimEvents();

		if (!$events) {
			return;
		}

		$servers = $this->scimServerService->getRegisteredScimServers();

		foreach ($servers as $server) {
			$config = $this->scimApiService->getScimServerConfig($server);

			$maxBulkOperations = $config['bulk']['maxOperations'];
			$isBulkOperationsSupported = $config['bulk']['supported'] && $maxBulkOperations;

			if ($isBulkOperationsSupported) {
				$operations = array_map(fn (array $e): array => $this->_generateEventParams($e, $server), $events);
				$this->scimApiService->sendBulkRequest($server, array_values(array_filter($operations)));
				continue;
			}

			foreach ($events as $event) {
				$operation = $this->_generateEventParams($event, $server);

				if ($operation) {
					$response = $this->networkService->request($server, $operation['path'], $operation['data'] ?? [], $operation['method']);
					$this->logger->debug(sprintf('SCIM %s %s', $operation['path'], $operation['method']), ['responseBody' => $response]);
				}
			}
		}

		// Cleanup processed update events
		// TODO: keep the event instead if the corresponding operation is unsuccessful for at least one server, write error to server log
		foreach ($events as $event) {
			$this->scimEventService->deleteScimEvent(new ScimEvent($event));
		}
	}

	private function _generateEventParams(array $event, array $server): array {
		if ($event['group_id']) {
			// Get the corresponding group ID on the SCIM server,
			// or use a bulk ID if group hasn't been created yet
			$groupId = $this->scimApiService->getScimServerGID($server, $event['group_id']) ?: ('bulkId:Group:' . $event['group_id']);
		}

		if ($event['user_id']) {
			// Get the corresponding user ID on the SCIM server,
			// or use a bulk ID if user hasn't been created yet
			$userId = $this->scimApiService->getScimServerUID($server, $event['user_id']) ?: ('bulkId:User:' . $event['user_id']);
		}

		if ($event['event'] === 'UserAddedEvent') {
			return [
				'method' => 'PATCH',
				'path' => '/Groups/' . $groupId,
				'data' => [
					'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
					'Operations' => [
						[
							'op' => 'add',
							'path' => 'members',
							'value' => [['value' => $userId]],
						],
					],
				],
			];
		}

		if ($event['event'] === 'UserChangedEvent') {
			if (!in_array($event['feature'], array_keys(self::ALLOWED_USER_ATTRIBUTES))) {
				return [];
			}

			return [
				'method' => 'PATCH',
				'path' => '/Users/' . $userId,
				'data' => [
					'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
					'Operations' => [
						[
							'op' => 'replace',
							'path' => self::ALLOWED_USER_ATTRIBUTES[$event['feature']],
							'value' => $event['value'],
						],
					],
				],
			];
		}

		if ($event['event'] === 'UserCreatedEvent') {
			return [
				'method' => 'POST',
				'path' => '/Users',
				'bulkId' => 'User:' . $event['user_id'],
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':User'],
					'active' => true,
					'externalId' => $event['user_id'],
					'userName' => $event['user_id'],
					'name' => ['formatted' => $event['user_id']],
					// Some servers may require an email address, so use a temporary one here
					'emails' => [['value' => 'change.me@example.com']],
				],
			];
		}

		if ($event['event'] === 'UserDeletedEvent') {
			return [
				'method' => 'DELETE',
				'path' => '/Users/' . $userId,
			];
		}

		if ($event['event'] === 'UserRemovedEvent') {
			return [
				'method' => 'PATCH',
				'path' => '/Groups/' . $groupId,
				'data' => [
					'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
					'Operations' => [
						[
							'op' => 'remove',
							'path' => 'members',
							'value' => [['value' => $userId]],
						],
					],
				],
			];
		}

		if ($event['event'] === 'GroupChangedEvent') {
			if ($event['feature'] !== 'displayName') {
				// Only displayName attribute is supported for now
				return [];
			}

			return [
				'method' => 'PATCH',
				'path' => '/Groups/' . $groupId,
				'data' => [
					'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
					'Operations' => [
						[
							'op' => 'replace',
							'path' => $event['feature'],
							'value' => $event['value'],
						],
					],
				],
			];
		}

		if ($event['event'] === 'GroupCreatedEvent') {
			return [
				'method' => 'POST',
				'path' => '/Groups',
				'bulkId' => 'Group:' . $event['group_id'],
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':Group'],
					'externalId' => $event['group_id'],
					'displayName' => $event['group_id'],
				],
			];
		}

		if ($event['event'] === 'GroupDeletedEvent') {
			return [
				'method' => 'DELETE',
				'path' => '/Groups/' . $groupId,
			];
		}

		// Default case (unknown event)
		$this->logger->warning(
			sprintf('Unable to process unknown event (%s), skipping.', $event['event']),
			['event' => $event],
		);
		return [];
	}
}
