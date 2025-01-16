<?php

declare(strict_types=1);

namespace OCA\ScimClient\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\ScimClient\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service to make network requests
 */
class NetworkService {

	public const ALLOWED_VERBS = ['get', 'post', 'put', 'patch', 'delete'];

	private IClient $client;

	public function __construct(
		IClientService $clientService,
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * @param array $server
	 * @param string $endpoint
	 * @param array $params
	 * @param string $method
	 * @param bool $jsonResponse
	 * @return array|mixed|resource|string|string[]
	 * @throws PreConditionNotMetException
	 */
	public function request(array $server, string $endpoint, array $params = [], string $method = 'GET',
		bool $jsonResponse = true) {
		$verb = strtolower($method);
		if (!in_array($verb, self::ALLOWED_VERBS)) {
			return ['error' => $this->l10n->t('Bad HTTP method')];
		}

		try {
			$url = $server['url'] . $endpoint;
			$credentials = $server['api_key'];
			$options = [
				'headers' => [
					'Authorization' => 'Bearer ' . $credentials,
					'Content-Type' => 'application/scim+json; charset=utf-8',
					'User-Agent' => Application::INTEGRATION_USER_AGENT,
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$url .= '?' . http_build_query($params);
				} else {
					$options['json'] = $params;
				}
			}

			$response = $this->client->{$verb}($url, $options);
			if ($response->getStatusCode() >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			}

			$body = $response->getBody();
			return $jsonResponse ? json_decode($body, true) : $body;
		} catch (ServerException|ClientException $e) {
			$body = $e->getResponse()->getBody();
			$this->logger->warning('SCIM API error : ' . $body, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('SCIM API error', ['exception' => $e, 'app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
