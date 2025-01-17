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
		private readonly ScimApiService $scimApiService,
		private readonly ScimEventService $scimEventService,
		private readonly ScimServerService $scimServerService,
		private readonly NetworkService $networkService,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);

		// Run every 5 minutes
		$this->setInterval(300);
	}

	protected function run($argument): void {
		$events = $this->scimEventService->getScimEvents();

		if (count($events) === 0) {
			return;
		}

		$servers = $this->scimServerService->getRegisteredScimServers();

		foreach ($servers as $server) {
			$config = $this->scimApiService->getScimServerConfig($server);

			$maxBulkOperations = $config['bulk']['maxOperations'];
			$isBulkOperationsSupported = $config['bulk']['supported'] && $maxBulkOperations > 0;

			if (!$isBulkOperationsSupported) {
				// TODO: add support for servers without bulk operations
				continue;
			}

			$operations = array_values(array_filter(array_map(fn (array $e): array => self::_generateEventParams($e, $server), $events)));
			$params = [
				'schemas' => [Application::SCIM_API_SCHEMA . ':BulkRequest'],
				'Operations' => $operations,
			];
			$this->networkService->request($server, '/Bulk', $params, 'POST');
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
			// or use bulk ID if group hasn't been created yet
			$groupResults = $this->networkService->request($server, '/Groups', ['attributes' => 'displayName'], 'GET');
			$serverGroups = $groupResults['Resources'];
			$serverGroupResults = array_filter($serverGroups, fn (array $g): bool => $event['group_id'] === $g['displayName']);
			$serverGroup = array_shift($serverGroupResults);
			$groupId = $serverGroup ? $serverGroup['id'] : 'bulkId:' . $event['group_id'];
		}

		if ($event['user_id']) {
			// Get the corresponding user ID on the SCIM server,
			// or use bulk ID if user hasn't been created yet
			$userResults = $this->networkService->request($server, '/Users', ['filter' => sprintf('externalId eq "%s"', $event['user_id'])], 'GET');
			if (!isset($userResults) || isset($userResults['error'])) {
				return [];
			}

			$user = array_shift($userResults['Resources']);
			$userId = $user ? $user['id'] : 'bulkId:' . $event['user_id'];
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
				'bulkId' => $event['user_id'],
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
				'bulkId' => $event['group_id'],
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':Group'],
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

		if ($event['event'] === 'SubAdminAddedEvent') {
			// TODO: handle event
			return [];
		}

		if ($event['event'] === 'SubAdminRemovedEvent') {
			// TODO: handle event
			return [];
		}

		// Default case (unknown event)
		$this->logger->warning(
			sprintf('Unable to process unknown event (%s), skipping.', $event['event']),
			['event' => $event],
		);
		return [];
	}
}
