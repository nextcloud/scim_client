<?php

declare(strict_types=1);

namespace OCA\ScimClient\Service;

use OCA\ScimClient\AppInfo\Application;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;

/**
 * Service to make requests to SCIM server
 */
class ScimApiService {

	private const ALLOWED_USER_ATTRIBUTES = [
		'active' => 'active',
		'displayName' => 'name.formatted',
		'eMailAddress' => 'emails.value',
	];

	public function __construct(
		private readonly IGroupManager $groupManager,
		private readonly IUserManager $userManager,
		private readonly NetworkService $networkService,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @param array $server
	 * @return array
	 * @throws PreConditionNotMetException
	 */
	public function getScimServerConfig(array $server): array {
		return (array)$this->networkService->request($server, '/ServiceProviderConfig', [], 'GET');
	}

	/**
	 * @param array $server
	 * @param array $operations
	 * @return void
	 * @throws PreConditionNotMetException
	 */
	public function sendBulkRequest(array $server, array $operations): void {
		// TODO: split bulk request according to $maxBulkOperations and adjust bulk/server IDs accordingly
		// in the meantime, it is expected that $maxBulkOperations should be large enough to handle any number of operations
		$params = [
			'schemas' => [Application::SCIM_API_SCHEMA . ':BulkRequest'],
			'Operations' => $operations,
		];
		$response = $this->networkService->request($server, '/Bulk', $params, 'POST');
		$this->logger->debug('SCIM /Bulk POST', ['responseBody' => $response]);

		// TODO: parse response and handle any errors from each operation
	}

	/**
	 * @param array $server
	 * @param string $userId
	 * @return string
	 * @throws PreConditionNotMetException
	 */
	public function getScimServerUID(array $server, string $userId): string {
		$params = [
			'filter' => sprintf('externalId eq "%s"', $userId),
		];
		$results = $this->networkService->request($server, '/Users', $params, 'GET');
		$this->logger->debug('SCIM /Users GET', ['responseBody' => $results]);

		if (!$results || $results['error']) {
			return '';
		}

		$user = array_shift($results['Resources']);
		return $user['id'] ?? '';
	}

	/**
	 * @param array $server
	 * @param string $groupId
	 * @return string
	 * @throws PreConditionNotMetException
	 */
	public function getScimServerGID(array $server, string $groupId): string {
		$params = [
			'filter' => sprintf('externalId eq "%s"', $groupId),
		];
		$results = $this->networkService->request($server, '/Groups', $params, 'GET');
		$this->logger->debug('SCIM /Groups GET', ['responseBody' => $results]);

		if (!$results || $results['error']) {
			return '';
		}

		$group = array_shift($results['Resources']);
		return $group['id'] ?? '';
	}

	/**
	 * @param array $server
	 * @return array
	 * @throws PreConditionNotMetException
	 */
	public function verifyScimServer(array $server): array {
		$config = $this->getScimServerConfig($server);

		if ($config['error']) {
			return [
				'error' => 'Unable to fetch SCIM server config',
				'response' => $config,
				'success' => false,
			];
		}

		$hasScimSchema = $config['schemas'][0] === Application::SCIM_CORE_SCHEMA . ':ServiceProviderConfig';
		$isBulkOperationsSupported = $config['bulk']['supported'] && $config['bulk']['maxOperations'];

		if (!$hasScimSchema) {
			return [
				'error' => 'Unable to fetch SCIM config',
				'success' => false,
			];
		}

		if (!$isBulkOperationsSupported) {
			return [
				'error' => 'Bulk operations feature is required',
				'success' => false,
			];
		}

		return ['success' => true];
	}

	/**
	 * @param array $server
	 * @return void
	 */
	public function syncScimServer(array $server): void {
		$config = $this->getScimServerConfig($server);

		if ($config['error']) {
			return;
		}

		$maxBulkOperations = $config['bulk']['maxOperations'];
		$isBulkOperationsSupported = $config['bulk']['supported'] && $maxBulkOperations;

		$users = $this->userManager->search('');
		$groups = $this->groupManager->search('');
		$serverUserIds = [];

		$userOperations = array_map(function (IUser $user) use ($server, &$serverUserIds, $isBulkOperationsSupported): array {
			// If user already exists in server, replace existing user, otherwise create new user
			$userId = $user->getUID();
			$email = $user->getEmailAddress();
			$serverUserId = $this->getScimServerUID($server, $userId);
			$serverUserIds[$userId] = $serverUserId;

			$syncUserOperation = [
				'method' => $serverUserId ? 'PUT' : 'POST',
				'path' => '/Users' . ($serverUserId ? ('/' . $serverUserId) : ''),
				'bulkId' => 'User:' . $userId,
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':User'],
					'active' => $user->isEnabled(),
					'externalId' => $userId,
					'userName' => $userId,
					'name' => [
						'formatted' => $user->getDisplayName(),
					],
					// Some servers may require an email address, so use a temporary one here
					'emails' => [['value' => $email ?: 'change.me@example.com']],
				],
			];

			if (!$isBulkOperationsSupported) {
				$response = $this->networkService->request($server, $syncUserOperation['path'], $syncUserOperation['data'], $syncUserOperation['method']);
				$this->logger->debug(sprintf('SCIM %s %s', $syncUserOperation['path'], $syncUserOperation['method']), ['responseBody' => $response]);
			}

			return $syncUserOperation;
		}, $users);

		$groupOperations = array_map(function (IGroup $group) use ($server, $serverUserIds, $isBulkOperationsSupported): array {
			$groupId = $group->getGID();
			$serverGroupId = $this->getScimServerGID($server, $groupId);

			// if the group does not exist in the server yet, create it, otherwise update it
			$syncGroupOperation = [
				'method' => $serverGroupId ? 'PUT' : 'POST',
				'path' => '/Groups' . ($serverGroupId ? ('/' . $serverGroupId) : ''),
				'bulkId' => 'Group:' . $groupId,
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':Group'],
					'displayName' => $group->getDisplayName(),
					'externalId' => $groupId,
				],
			];

			if (!$isBulkOperationsSupported) {
				$response = $this->networkService->request($server, $syncGroupOperation['path'], $syncGroupOperation['data'], $syncGroupOperation['method']);
				$this->logger->debug(sprintf('SCIM %s %s', $syncGroupOperation['path'], $syncGroupOperation['method']), ['responseBody' => $response]);
				$serverGroupId = $response['id'];
			}

			$operations = [$syncGroupOperation];

			$addGroupUsers = array_map(function (IUser $user) use ($serverUserIds): array {
				$userId = $user->getUID();

				return [
					'op' => 'add',
					'path' => 'members',
					'value' => [['value' => $serverUserIds[$userId] ?: ('bulkId:User:' . $userId)]],
				];
			}, $group->getUsers());

			if ($addGroupUsers) {
				// Copy group members to server
				$syncMembersOperation = [
					'method' => 'PATCH',
					'path' => '/Groups/' . ($serverGroupId ?: ('bulkId:Group:' . $groupId)),
					'data' => [
						'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
						'Operations' => $addGroupUsers,
					],
				];

				if (!$isBulkOperationsSupported) {
					$response = $this->networkService->request($server, $syncMembersOperation['path'], $syncMembersOperation['data'], $syncMembersOperation['method']);
					$this->logger->debug(sprintf('SCIM %s %s', $syncMembersOperation['path'], $syncMembersOperation['method']), ['responseBody' => $response]);
				}

				$operations[] = $syncMembersOperation;
			}

			return $operations;
		}, $groups);

		if ($isBulkOperationsSupported) {
			$bulkOperations = array_values(array_merge($userOperations, ...$groupOperations));
			$this->sendBulkRequest($server, $bulkOperations);
		}
	}

	/**
	 * @param array $server
	 * @param array $events
	 * @return void
	 */
	public function updateScimServer(array $server, array $events): void {
		$config = $this->getScimServerConfig($server);

		$maxBulkOperations = $config['bulk']['maxOperations'];
		$isBulkOperationsSupported = $config['bulk']['supported'] && $maxBulkOperations;

		if ($isBulkOperationsSupported) {
			$operations = array_map(fn (array $e): array => $this->_generateEventParams($e, $server), $events);
			$this->sendBulkRequest($server, array_values(array_filter($operations)));
			return;
		}

		foreach ($events as $event) {
			$operation = $this->_generateEventParams($event, $server);

			if ($operation) {
				$response = $this->networkService->request($server, $operation['path'], $operation['data'] ?? [], $operation['method']);
				$this->logger->debug(sprintf('SCIM %s %s', $operation['path'], $operation['method']), ['responseBody' => $response]);
			}
		}
	}

	private function _generateEventParams(array $event, array $server): array {
		if ($event['group_id']) {
			// Get the corresponding group ID on the SCIM server,
			// or use a bulk ID if group hasn't been created yet
			$serverGroupId = $this->getScimServerGID($server, $event['group_id']) ?: ('bulkId:Group:' . $event['group_id']);
		}

		if ($event['user_id']) {
			// Get the corresponding user ID on the SCIM server,
			// or use a bulk ID if user hasn't been created yet
			$serverUserId = $this->getScimServerUID($server, $event['user_id']) ?: ('bulkId:User:' . $event['user_id']);
		}

		if ($event['event'] === 'UserAddedEvent') {
			return [
				'method' => 'PATCH',
				'path' => '/Groups/' . $serverGroupId,
				'data' => [
					'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
					'Operations' => [
						[
							'op' => 'add',
							'path' => 'members',
							'value' => [['value' => $serverUserId]],
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
				'path' => '/Users/' . $serverUserId,
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
			// If user already exists on server, replace the existing user instead
			$serverUserExists = !str_starts_with($serverUserId, 'bulkId:');

			return [
				'method' => $serverUserExists ? 'PUT' : 'POST',
				'path' => '/Users' . ($serverUserExists ? ('/' . $serverUserId) : ''),
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
				'path' => '/Users/' . $serverUserId,
			];
		}

		if ($event['event'] === 'UserRemovedEvent') {
			return [
				'method' => 'PATCH',
				'path' => '/Groups/' . $serverGroupId,
				'data' => [
					'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
					'Operations' => [
						[
							'op' => 'remove',
							'path' => 'members',
							'value' => [['value' => $serverUserId]],
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
				'path' => '/Groups/' . $serverGroupId,
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
			// If group already exists on server, replace the existing group instead
			$serverGroupExists = !str_starts_with($serverGroupId, 'bulkId:');

			return [
				'method' => $serverGroupExists ? 'PUT' : 'POST',
				'path' => '/Groups' . ($serverGroupExists ? ('/' . $serverGroupId) : ''),
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
				'path' => '/Groups/' . $serverGroupId,
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
