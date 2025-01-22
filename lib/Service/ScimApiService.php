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

		if (!$isBulkOperationsSupported) {
			// TODO: add support for servers without bulk operations
			return;
		}

		$users = $this->userManager->search('');
		$groups = $this->groupManager->search('');

		$userOperations = array_map(function (IUser $user) use ($server): array {
			// If user already exists in server, replace existing user, otherwise create new user
			$userId = $user->getUID();
			$email = $user->getEmailAddress();
			$serverId = $this->getScimServerUID($server, $userId);

			return [
				'method' => $serverId ? 'PUT' : 'POST',
				'path' => '/Users' . ($serverId ? ('/' . $serverId) : ''),
				'bulkId' => $userId,
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':User'],
					'active' => $user->isEnabled(),
					'externalId' => $userId,
					'userName' => $userId,
					'name' => [
						'formatted' => $user->getDisplayName(),
					],
					'emails' => $email ? [['value' => $email]] : [],
				],
			];
		}, $users);

		$groupOperations = array_map(function (IGroup $group) use ($server): array {
			$operations = [];

			$groupId = $group->getGID();
			$serverId = $this->getScimServerGID($server, $groupId);

			if (!$serverId) {
				// Group does not exist in server yet, create it first
				$operations[] = [
					'method' => 'POST',
					'path' => '/Groups',
					'bulkId' => $groupId,
					'data' => [
						'schemas' => [Application::SCIM_CORE_SCHEMA . ':Group'],
						'displayName' => $group->getDisplayName(),
						'externalId' => $groupId,
					],
				];
			}

			$addGroupUsers = array_map(static fn (IUser $user): array => [
				'op' => 'add',
				'path' => 'members',
				'value' => [['value' => 'bulkId:' . $user->getUID()]],
			], $group->getUsers());

			if ($addGroupUsers) {
				// Copy group members to server
				$operations[] = [
					'method' => 'PATCH',
					'path' => '/Groups/' . ($serverId ?: ('bulkId:' . $groupId)),
					'data' => [
						'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
						'Operations' => $addGroupUsers,
					],
				];
			}

			return $operations;
		}, $groups);

		$bulkOperations = array_values(array_merge($userOperations, ...$groupOperations));
		$this->sendBulkRequest($server, $bulkOperations);
	}
}
