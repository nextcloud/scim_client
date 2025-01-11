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

	private IClient $client;

	public function __construct(
		IClientService $clientService,
		private IConfig $config,
		private LoggerInterface $logger,
		private IL10N $l10n,
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
		try {
			$url = $server['url'] . $endpoint;
			$credentials = base64_encode($server['api_key']);
			$options = [
				'headers' => [
					'Authorization' => 'Basic ' . $credentials,
					'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
					'User-Agent' => Application::INTEGRATION_USER_AGENT,
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$url .= '?' . http_build_query($params);
				} else {
					$options['body'] = $params;
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} elseif ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} elseif ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			}
			if ($jsonResponse) {
				return json_decode($body, true);
			}
			return $body;
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
