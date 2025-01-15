<?php

declare(strict_types=1);

namespace OCA\ScimClient\Controller;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Db\ScimServer;
use OCA\ScimClient\Service\ScimApiService;
use OCA\ScimClient\Service\ScimEventService;
use OCA\ScimClient\Service\ScimServerService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\Security\ICrypto;

class ScimServerController extends ApiController {

	public function __construct(
		IRequest $request,
		private readonly ScimApiService $scimApiService,
		private readonly ScimEventService $scimEventService,
		private readonly ScimServerService $scimServerService,
		private readonly ICrypto $crypto,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[FrontpageRoute(verb: 'GET', url: '/servers')]
	public function getAllScimServers(): Response {
		$servers = $this->scimServerService->getRegisteredScimServers();

		foreach ($servers as &$server) {
			// Mask API key with dummy secret if set
			if (!empty($server['api_key'])) {
				$server['api_key'] = Application::DUMMY_SECRET;
			}
		}

		return new JSONResponse($servers);
	}

	#[PasswordConfirmationRequired]
	#[FrontpageRoute(verb: 'POST', url: '/servers')]
	public function registerScimServer(array $params): Response {
		$server = $this->scimServerService->registerScimServer($params);

		if (isset($server)) {
			$server = $server->jsonSerialize();
			$server['api_key'] = $this->crypto->decrypt($server['api_key']);
			$this->scimApiService->syncScimServer($server);
		}

		return new JSONResponse([
			'success' => (bool)$server,
			'server' => $server,
		]);
	}

	#[PasswordConfirmationRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/servers/{id}')]
	public function updateScimServer(int $id, array $params): Response {
		// Restore original API key if dummy secret is provided
		$apiKey = $params['api_key'] ?? null;
		if ($apiKey === Application::DUMMY_SECRET) {
			$server = $this->scimServerService->getScimServer($id);
			$params['api_key'] = $server->getApiKey() ?? '';
		} elseif (!empty($apiKey)) {
			// New API key provided, encrypt it
			$params['api_key'] = $this->crypto->encrypt($apiKey);
		}

		// Update the server config
		$params['id'] = $id;
		$updatedServer = new ScimServer($params);
		$updatedServer = $this->scimServerService->updateScimServer($updatedServer);

		if (!$updatedServer) {
			return new JSONResponse([
				'success' => false,
				'server' => null,
			]);
		}

		// Mask API key with dummy secret if set
		if (!empty($updatedServer->getApiKey() ?? null)) {
			$updatedServer->setApiKey(Application::DUMMY_SECRET);
		}

		return new JSONResponse([
			'success' => true,
			'server' => $updatedServer,
		]);
	}

	#[PasswordConfirmationRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/servers/{id}')]
	public function unregisterScimServer(int $id): Response {
		$server = $this->scimServerService->getScimServer($id);
		$server = $this->scimServerService->unregisterScimServer($server);

		// Do not show API key in response
		if (isset($server)) {
			$server = $server->jsonSerialize();
			unset($server['api_key']);
		}

		return new JSONResponse([
			'success' => (bool)$server,
			'server' => $server,
		]);
	}

	#[FrontpageRoute(verb: 'POST', url: '/servers/{id}/verify')]
	public function verifyExistingScimServer(int $id): Response {
		$server = $this->scimServerService->getScimServer($id)->jsonSerialize();
		if (!empty($server['api_key'])) {
			$server['api_key'] = $this->crypto->decrypt($server['api_key']);
		}

		return new JSONResponse($this->scimApiService->verifyScimServer($server));
	}

	#[FrontpageRoute(verb: 'POST', url: '/servers/verify')]
	public function verifyNewScimServer(array $server): Response {
		return new JSONResponse($this->scimApiService->verifyScimServer($server));
	}

	#[FrontpageRoute(verb: 'POST', url: '/servers/{id}/sync')]
	public function syncScimServer(int $id): Response {
		$server = $this->scimServerService->getScimServer($id);

		if (isset($server)) {
			$server = $server->jsonSerialize();
			$server['api_key'] = $this->crypto->decrypt($server['api_key']);
			$this->scimApiService->syncScimServer($server);
		}

		return new JSONResponse([
			'success' => (bool)$server,
			'server_id' => $id,
		]);
	}
}
