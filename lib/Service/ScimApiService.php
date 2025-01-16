<?php

declare(strict_types=1);

namespace OCA\ScimClient\Service;

use OCA\ScimClient\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\PreConditionNotMetException;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * Service to make requests to SCIM server
 */
class ScimApiService {

	private IClient $client;

	public function __construct(
		IClientService $clientService,
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
		private readonly IURLGenerator $urlGenerator,
		private readonly ICrypto $crypto,
		private readonly IGroupManager $groupManager,
		private readonly IUserManager $userManager,
		private readonly NetworkService $networkService,
	) {
		$this->client = $clientService->newClient();
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
	 * @return array
	 * @throws PreConditionNotMetException
	 */
	public function verifyScimServer(array $server): array {
		$config = $this->getScimServerConfig($server);

		if (isset($config['error'])) {
			$reponse['success'] = false;
			return $config;
		}

		$hasScimSchema = $config['schemas'][0] === Application::SCIM_CORE_SCHEMA . ':ServiceProviderConfig';
		$isBulkOperationsSupported = $config['bulk']['supported'] && $config['bulk']['maxOperations'] > 0;

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

		if (isset($config['error'])) {
			return;
		}

		$maxBulkOperations = $config['bulk']['maxOperations'];
		$isBulkOperationsSupported = $config['bulk']['supported'] && $maxBulkOperations > 0;

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

			$userResults = $this->networkService->request($server, '/Users', ['filter' => sprintf('externalId eq "%s"', $userId)], 'GET');
			if (!isset($userResults) || isset($userResults['error'])) {
				return [];
			}

			$userExists = $userResults['totalResults'] > 0;
			$userPath = $userExists ? '/' . $userResults['Resources'][0]['id'] : '';

			return [
				'method' => $userExists ? 'PUT' : 'POST',
				'path' => '/Users' . $userPath,
				'bulkId' => $userId,
				'data' => [
					'schemas' => [Application::SCIM_CORE_SCHEMA . ':User'],
					'active' => $user->isEnabled(),
					'externalId' => $userId,
					'userName' => $userId,
					'displayName' => $user->getDisplayName(),
					'emails' => is_string($email) && mb_strlen($email) ? [['value' => $email]] : [],
				],
			];
		}, $users);

		$groupResults = $this->networkService->request($server, '/Groups', ['attributes' => 'displayName'], 'GET');
		$serverGroups = $groupResults['Resources'];

		$groupOperations = array_map(function (IGroup $group) use ($serverGroups): array {
			$operations = [];

			$displayName = $group->getDisplayName();
			$serverGroupResults = array_filter($serverGroups, fn (array $g): bool => $displayName === $g['displayName']);
			$serverGroup = array_shift($serverGroupResults);

			if (is_null($serverGroup)) {
				// Group does not exist in server yet, create it first
				$operations[] = [
					'method' => 'POST',
					'path' => '/Groups',
					'bulkId' => $displayName,
					'data' => [
						'schemas' => [Application::SCIM_CORE_SCHEMA . ':Group'],
						'displayName' => $displayName,
					],
				];
			}

			$addGroupUsers = array_map(static fn (IUser $user): array => [
				'op' => 'add',
				'path' => 'members',
				'value' => [['value' => 'bulkId:' . $user->getUID()]],
			], $group->getUsers());

			if (count($addGroupUsers) > 0) {
				// Copy group members to server
				$operations[] = [
					'method' => 'PATCH',
					'path' => '/Groups/' . (isset($serverGroup) ? $serverGroup['id'] : 'bulkId:' . $displayName),
					'data' => [
						'schemas' => [Application::SCIM_API_SCHEMA . ':PatchOp'],
						'Operations' => $addGroupUsers,
					],
				];
			}

			return $operations;
		}, $groups);

		// Try sending the bulk operation regardless of $maxBulkOperations value
		// All operations should be done in a single bulk request so that the bulkIds are correctly referenced
		$params = [
			'schemas' => [Application::SCIM_API_SCHEMA . ':BulkRequest'],
			'Operations' => array_values(array_merge($userOperations, ...$groupOperations)),
		];
		$this->networkService->request($server, '/Bulk', $params, 'POST');
	}
}
