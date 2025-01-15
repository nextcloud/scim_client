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
	 * @param array $server
	 * @param array $params
	 * @return array
	 * @throws PreConditionNotMetException
	 */
	public function syncScimServer(array $server, array $params): array {
		return (array)$this->networkService->request($server, '/Bulk', $params, 'POST');
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
}
