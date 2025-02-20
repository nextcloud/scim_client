<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Controller;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Db\ScimServer;
use OCA\ScimClient\Service\ScimApiService;
use OCA\ScimClient\Service\ScimEventService;
use OCA\ScimClient\Service\ScimServerService;
use OCA\ScimClient\Service\ScimSyncRequestService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

class ScimServerController extends ApiController {

	public function __construct(
		IRequest $request,
		private readonly ScimApiService $scimApiService,
		private readonly ScimEventService $scimEventService,
		private readonly ScimServerService $scimServerService,
		private readonly ScimSyncRequestService $scimSyncRequestService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[FrontpageRoute(verb: 'GET', url: '/servers')]
	public function getAllScimServers(): Response {
		$servers = $this->scimServerService->getRegisteredScimServers();

		foreach ($servers as &$server) {
			// Mask API key with dummy secret if set
			if ($server['api_key']) {
				$server['api_key'] = Application::DUMMY_SECRET;
			}
		}

		return new JSONResponse($servers);
	}

	#[PasswordConfirmationRequired]
	#[FrontpageRoute(verb: 'POST', url: '/servers')]
	public function registerScimServer(array $params): Response {
		$server = $this->scimServerService->registerScimServer($params);

		if ($server) {
			$syncParams = ['server_id' => $server->getId()];
			$this->scimSyncRequestService->addScimSyncRequest($syncParams);
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
		if ($params['api_key'] === Application::DUMMY_SECRET) {
			$server = $this->scimServerService->getScimServer($id);
			$params['api_key'] = $server->getApiKey() ?? '';
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
		if ($updatedServer->getApiKey()) {
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
		if ($server) {
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
		$server = $this->scimServerService->getScimServer($id);

		if (!$server) {
			return new JSONResponse([
				'error' => 'SCIM server not found',
				'success' => false,
			]);
		}

		return new JSONResponse($this->scimApiService->verifyScimServer($server->jsonSerialize()));
	}

	#[FrontpageRoute(verb: 'POST', url: '/servers/verify')]
	public function verifyNewScimServer(array $server): Response {
		return new JSONResponse($this->scimApiService->verifyScimServer($server));
	}

	#[FrontpageRoute(verb: 'POST', url: '/servers/{id}/sync')]
	public function syncScimServer(int $id): Response {
		$params = ['server_id' => $id];
		$request = $this->scimSyncRequestService->addScimSyncRequest($params);

		return new JSONResponse([
			'success' => (bool)$request,
			'request' => $request,
		]);
	}
}
