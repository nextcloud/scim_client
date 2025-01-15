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
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class Sync extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		private readonly ScimApiService $scimApiService,
		private readonly ScimEventService $scimEventService,
		private readonly ScimServerService $scimServerService,
		private readonly NetworkService $networkService,
		private readonly IUserManager $userManager,
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
		$operations = array_values(array_filter(array_map('self::_generateEventParams', $events)));

		foreach ($servers as $server) {
			$config = $this->scimApiService->getScimServerConfig($server);

			$maxBulkOperations = $config['bulk']['maxOperations'];
			$isBulkOperationsSupported = $config['bulk']['supported'] && $maxBulkOperations > 0;

			if (!$isBulkOperationsSupported) {
				// TODO: add support for servers without bulk operations
				continue;
			}

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

	private function _generateEventParams(array $event): array {
		if ($event['event'] === 'UserAddedEvent') {
			// TODO: handle event
			return [];
		}

		if ($event['event'] === 'UserChangedEvent') {
			// TODO: handle event
			return [];
		}

		if ($event['event'] === 'UserCreatedEvent') {
			$newUser = $this->userManager->get($event['user_id']);

			if (!$newUser) {
				$this->logger->warning(
					sprintf('Unable to find user with ID "%s", skipping.', $event['user_id']),
					['event' => $event],
				);
				return [];
			}

			$email = $newUser->getEmailAddress();

			return [
				'method' => 'POST',
				'path' => '/Users',
				'bulkId' => $event['user_id'],
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':User'],
					'active' => true,
					'externalId' => $event['user_id'],
					'userName' => $event['user_id'],
					'displayName' => $newUser->getDisplayName(),
					'emails' => is_string($email) && mb_strlen($email) ? [['value' => $email]] : [],
				],
			];
		}

		if ($event['event'] === 'UserDeletedEvent') {
			// TODO: handle event
			return [];
		}

		if ($event['event'] === 'UserRemovedEvent') {
			// TODO: handle event
			return [];
		}

		if ($event['event'] === 'GroupChangedEvent') {
			// TODO: handle event
			return [];
		}

		if ($event['event'] === 'GroupCreatedEvent') {
			// TODO: handle event
			return [];
		}

		if ($event['event'] === 'GroupDeletedEvent') {
			// TODO: handle event
			return [];
		}

		if ($event['event'] === 'PasswordUpdatedEvent') {
			// TODO: handle event
			return [];
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
