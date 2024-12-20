<?php

declare(strict_types=1);

namespace OCA\ScimClient\Service;

use OCA\ScimClient\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IL10N;
use OCP\IURLGenerator;
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
		private LoggerInterface $logger,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ICrypto $crypto,
		private NetworkService $networkService,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * @param array $serverParams
	 * @return array
	 * @throws PreConditionNotMetException
	 */
	public function verifyScimServer(array $server): array {
		$response = $this->networkService->request($server, '/ResourceTypes', [], 'GET');

		if (isset($response['error'])) {
			$reponse['success'] = false;
			return (array)$response;
		}

		$hasUserSchema = count(array_filter($response['Resources'], static function (array $resource): bool {
			return $resource['schema'] === Application::SCIM_CORE_SCHEMA . ':User';
		}));

		$hasGroupSchema = count(array_filter($response['Resources'], static function (array $resource): bool {
			return $resource['schema'] === Application::SCIM_CORE_SCHEMA . ':Group';
		}));

		if ($hasUserSchema && $hasGroupSchema) {
			return ['success' => true];
		}

		return [
			'error' => 'Missing required SCIM schemas',
			'success' => false,
		];
	}
}
