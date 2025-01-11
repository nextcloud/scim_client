<?php

declare(strict_types=1);

namespace OCA\ScimClient\Cron;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Db\ScimEvent;
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
		private readonly IUserManager $userManager,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);

		// Run every 5 minutes
		$this->setInterval(300);
	}

	protected function run($argument): void {
		$events = $this->scimEventService->getScimEvents();
		$servers = $this->scimServerService->getRegisteredScimServers();

		// Get only events for full server sync on requested servers
		$syncEvents = array_filter($events, static fn (array $e): bool => $e['event'] === Application::SYNC_REQUEST_EVENT);
		// All other events apply recent changes to remaining servers
		$updateEvents = array_filter($events, static fn (array $e): bool => $e['event'] !== Application::SYNC_REQUEST_EVENT);

		// Get only servers to be synced
		$syncServerIds = array_unique(array_map(static fn (array $e): int => $e['server_id'], $syncEvents));
		$syncServers = array_filter($servers, fn (array $s): bool => in_array($s['id'], $syncServerIds));
		// All other servers will receive recent changes only
		$updateServers = array_filter($servers, fn (array $s): bool => !in_array($s['id'], $syncServerIds));

		// Do the update operation
		if (count($updateEvents)) {
			if (count($updateServers)) {
				$params = [
					'schemas' => [ Application::SCIM_API_SCHEMA . ':BulkRequest' ],
					'Operations' => array_values(array_filter(array_map('self::_generateUpdateEventParams', $updateEvents))),
				];

				if (count($params['Operations'])) {
					foreach ($updateServers as $server) {
						$this->scimApiService->syncScimServer($server, $params);
					}
				}
			}

			// Cleanup processed update events
			// TODO: keep the event instead if the corresponding operation is unsuccessful for at least one server, write error to server log
			foreach ($updateEvents as $event) {
				$this->scimEventService->deleteScimEvent(new ScimEvent($event));
			}
		}

		// Do the sync operation
		foreach ($syncServers as $server) {
			// $this->scimApiService->syncScimServer($server, $this->_generateSyncEventParams($server));
		}

		// Cleanup processed sync events
		// TODO: keep the events instead if the corresponding server operation is unsuccessful, write error to server log
		foreach ($syncEvents as $event) {
			$this->scimEventService->deleteScimEvent(new ScimEvent($event));
		}
	}

	private function _generateUpdateEventParams(array $event): array {
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
					[ 'event' => $event ],
				);
				return [];
			}

			$email = $newUser->getEmailAddress();

			return [
				'method' => 'POST',
				'path' => '/Users',
				'bulkId' => $event['user_id'],
				'data' => [
					'schemas' => [ Application::SCIM_CORE_SCHEMA . ':User' ],
					'externalId' => $event['user_id'],
					'userName' => $event['user_id'],
					'displayName' => $newUser->getDisplayName(),
					'password' => $event['password'],
					'emails' => is_string($email) && mb_strlen($email) ? [[ 'value' => $email ]] : [],
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
			[ 'event' => $event ],
		);
		return [];
	}

	private function _generateSyncEventParams(array $server): array {
		// TODO: get all Nextcloud users/groups, create a POST/PUT operation for each
		// TODO: check if user/group already exists in server, create/update as appropriate

		return [];
	}
}
