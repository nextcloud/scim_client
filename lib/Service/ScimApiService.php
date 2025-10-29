<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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
	 * @param int $maxOperations
	 * @return array
	 * @throws PreConditionNotMetException
	 */
	public function sendBulkRequest(array $server, array &$operations, int $maxOperations): array {
		$responses = [];

		while ($operations) {
			$params = [
				'schemas' => [Application::SCIM_API_SCHEMA . ':BulkRequest'],
				'Operations' => array_splice($operations, 0, $maxOperations),
			];

			$response = $this->networkService->request($server, '/Bulk', $params, 'POST');
			$success = in_array(Application::SCIM_API_SCHEMA . ':BulkResponse', $response['schemas'] ?? []);
			$this->logger->{ $success ? 'debug' : 'error' }('SCIM /Bulk POST', ['responseBody' => $response]);
			$responses = array_merge($responses, $response['Operations'] ?? []);
		}

		return $responses;
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
		$success = $results && empty($results['error']);
		$this->logger->{ $success ? 'debug' : 'error' }('SCIM /Users GET', ['responseBody' => $results]);

		if (!$success) {
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
		$success = $results && empty($results['error']);
		$this->logger->{ $success ? 'debug' : 'error' }('SCIM /Groups GET', ['responseBody' => $results]);

		if (!$success) {
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

		if (isset($config['error'])) {
			return [
				'error' => 'Unable to fetch SCIM server config',
				'response' => $config,
				'success' => false,
			];
		}

		$hasScimSchema = $config['schemas'][0] === Application::SCIM_CORE_SCHEMA . ':ServiceProviderConfig';
		$isBulkOperationsSupported = $config['bulk']['supported'] && ($config['bulk']['maxOperations'] > 1);

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
	 * @return array
	 */
	public function syncScimServer(array $server): array {
		$config = $this->getScimServerConfig($server);

		if (isset($config['error'])) {
			return [];
		}

		$maxBulkOperations = $config['bulk']['maxOperations'];
		$isBulkOperationsSupported = $config['bulk']['supported'] && ($maxBulkOperations > 1);

		$users = $this->userManager->search('');
		$groups = $this->groupManager->search('');
		$serverIds = [];
		$events = [];

		$userOperations = array_map(function (IUser $user) use ($server, &$serverIds, &$events, $isBulkOperationsSupported): array {
			$userId = $user->getUID();
			$userBulkId = 'User:' . $userId;
			$serverIds[$userBulkId] = $this->getScimServerUID($server, $userId);

			// If user already exists in server, replace existing user, otherwise create new user
			$syncUserOperation = [
				'method' => $serverIds[$userBulkId] ? 'PUT' : 'POST',
				'path' => '/Users' . ($serverIds[$userBulkId] ? ('/' . $serverIds[$userBulkId]) : ''),
				'bulkId' => $userBulkId,
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':User'],
					'active' => $user->isEnabled(),
					'externalId' => $userId,
					'userName' => $userId,
					'name' => [
						'formatted' => $user->getDisplayName(),
					],
					// Some servers may require an email address, so use a temporary one here
					'emails' => [['value' => $user->getEmailAddress() ?: 'change.me@example.com']],
				],
			];

			$userEvent = [
				'event' => $serverIds[$userBulkId] ? 'User Info Updated' : 'User Created',
				'id' => $userId,
				'success' => false,
			];

			if (!$isBulkOperationsSupported) {
				$response = $this->networkService->request($server, $syncUserOperation['path'], $syncUserOperation['data'], $syncUserOperation['method']);
				$userEvent['success'] = in_array(Application::SCIM_CORE_SCHEMA . ':User', $response['schemas'] ?? []);
				$this->logger->{ $userEvent['success'] ? 'debug' : 'error' }(sprintf('SCIM %s %s', $syncUserOperation['path'], $syncUserOperation['method']), ['responseBody' => $response]);
				$serverIds[$userBulkId] = $response['id'] ?? '';
			}

			$events[$userBulkId] = $userEvent;
			return $syncUserOperation;
		}, $users);

		$groupOperations = array_map(function (IGroup $group) use ($server, &$serverIds, &$events, $isBulkOperationsSupported): array {
			$groupId = $group->getGID();
			$groupBulkId = 'Group:' . $groupId;
			$serverIds[$groupBulkId] = $this->getScimServerGID($server, $groupId);

			// If group already exists in server, replace existing group, otherwise create new group
			$syncGroupOperation = [
				'method' => $serverIds[$groupBulkId] ? 'PUT' : 'POST',
				'path' => '/Groups' . ($serverIds[$groupBulkId] ? ('/' . $serverIds[$groupBulkId]) : ''),
				'bulkId' => $groupBulkId,
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':Group'],
					'displayName' => $group->getDisplayName(),
					'externalId' => $groupId,
				],
			];

			$groupEvent = [
				'event' => $serverIds[$groupBulkId] ? 'Group Info Updated' : 'Group Created',
				'id' => $groupId,
				'success' => false,
			];

			if (!$isBulkOperationsSupported) {
				$response = $this->networkService->request($server, $syncGroupOperation['path'], $syncGroupOperation['data'], $syncGroupOperation['method']);
				$groupEvent['success'] = in_array(Application::SCIM_CORE_SCHEMA . ':Group', $response['schemas'] ?? []);
				$this->logger->{ $groupEvent['success'] ? 'debug' : 'error' }(sprintf('SCIM %s %s', $syncGroupOperation['path'], $syncGroupOperation['method']), ['responseBody' => $response]);
				$serverIds[$groupBulkId] = $response['id'] ?? '';
			}

			$events[$groupBulkId] = $groupEvent;
			return $syncGroupOperation;
		}, $groups);

		$bulkOperations = array_values(array_merge($userOperations, $groupOperations));
		if ($isBulkOperationsSupported && ((2 * count($groups) + count($users)) > $maxBulkOperations)) {
			// Total number of operations may exceed $maxBulkOperations, so push all users and groups first
			// Retrieve server IDs of new users and groups for the remaining operations
			$responses = $this->sendBulkRequest($server, $bulkOperations, $maxBulkOperations);

			foreach ($responses as $response) {
				$events[$response['bulkId']]['success'] = $response['status'] < 400;

				if (!$events[$response['bulkId']]['success']) {
					$this->logger->error('SCIM /Bulk operation failed', ['responseBody' => $response]);
					continue;
				}

				if (($response['method'] === 'POST') && preg_match('/\/(Groups|Users)\/([-[:xdigit:]]+)$/', $response['location'] ?? '', $matches)) {
					$serverIds[$response['bulkId']] = $matches[2];
				}
			}
		}

		$memberOperations = [];
		foreach ($groups as $group) {
			$addGroupUsers = array_map(function (IUser $user) use ($serverIds): array {
				$userBulkId = 'User:' . $user->getUID();

				return [
					'op' => 'add',
					'path' => 'members',
					'value' => [['value' => $serverIds[$userBulkId] ?: ('bulkId:' . $userBulkId)]],
				];
			}, $group->getUsers());

			if ($addGroupUsers) {
				$groupId = $group->getGID();
				$groupBulkId = 'Group:' . $groupId;

				// Copy group members to server
				$syncMembersOperation = [
					'method' => 'PATCH',
					'path' => '/Groups/' . ($serverIds[$groupBulkId] ?: ('bulkId:' . $groupBulkId)),
					'bulkId' => 'Members:' . $groupBulkId,
					'data' => [
						'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
						'Operations' => array_values($addGroupUsers),
					],
				];

				$membersEvent = [
					'event' => 'Group Members Updated',
					'id' => $groupId,
					'success' => false,
				];

				if (!$isBulkOperationsSupported) {
					$response = $this->networkService->request($server, $syncMembersOperation['path'], $syncMembersOperation['data'], $syncMembersOperation['method']);
					$membersEvent['success'] = in_array(Application::SCIM_API_SCHEMA . ':PatchOp', $response['schemas'] ?? []);
					$this->logger->{ $membersEvent['success'] ? 'debug' : 'error' }(sprintf('SCIM %s %s', $syncMembersOperation['path'], $syncMembersOperation['method']), ['responseBody' => $response]);
				}

				$events['Members:' . $groupBulkId] = $membersEvent;
				$memberOperations[] = $syncMembersOperation;
			}
		}

		if ($isBulkOperationsSupported) {
			$bulkOperations = array_values(array_merge($bulkOperations, $memberOperations));
			$responses = $this->sendBulkRequest($server, $bulkOperations, $maxBulkOperations);

			foreach ($responses as $response) {
				$events[$response['bulkId']]['success'] = $response['status'] < 400;

				if (!$events[$response['bulkId']]['success']) {
					$this->logger->error('SCIM /Bulk operation failed', ['responseBody' => $response]);
				}
			}
		}

		return $events;
	}

	/**
	 * @param array $server
	 * @param array $events
	 * @return void
	 */
	public function updateScimServer(array $server, array &$events): void {
		$config = $this->getScimServerConfig($server);

		$maxBulkOperations = $config['bulk']['maxOperations'];
		$isBulkOperationsSupported = $config['bulk']['supported'] && ($maxBulkOperations > 1);

		if (!$isBulkOperationsSupported) {
			foreach ($events as &$event) {
				$operation = $this->_generateEventParams($event, $server);
				$event['success'] = true;

				if ($operation) {
					$response = $this->networkService->request($server, $operation['path'], $operation['data'] ?? [], $operation['method']);
					$event['success'] = in_array(Application::SCIM_API_SCHEMA . ':Error', $response['schemas'] ?? []);
					$this->logger->{ $event['success'] ? 'debug' : 'error' }(sprintf('SCIM %s %s', $operation['path'], $operation['method']), ['responseBody' => $response]);
				}
			}

			return;
		}

		$pendingEvents = $events;
		$i = 0;

		while ($pendingEvents) {
			$operations = [];

			while ($pendingEvents && (count($operations) < $maxBulkOperations)) {
				$nextEvent = array_shift($pendingEvents);

				$operation = $this->_generateEventParams($nextEvent, $server);
				if ($operation) {
					$operations[$i] = $operation;
				}

				$i++;
			}

			$operationEvents = array_keys($operations);
			$operations = array_values($operations);
			$responses = $this->sendBulkRequest($server, $operations, $maxBulkOperations);

			for ($j = 0; $j < count($responses); $j++) {
				$success = $responses[$j]['status'] < 400;
				$events[$operationEvents[$j]]['success'] = $events[$operationEvents[$j]]['success'] && $success;

				if (!$success) {
					$this->logger->error('SCIM /Bulk operation failed', ['responseBody' => $responses[$j]]);
				}
			}
		}
	}

	private function _generateEventParams(array $event, array $server): array {
		if (isset($event['group_id'])) {
			// Get the corresponding group ID on the SCIM server,
			// or use a bulk ID if group hasn't been created yet
			$serverGroupId = $this->getScimServerGID($server, $event['group_id']) ?: ('bulkId:Group:' . $event['group_id']);
		}

		if (isset($event['user_id'])) {
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
