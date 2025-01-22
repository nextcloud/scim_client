<?php

declare(strict_types=1);

namespace OCA\ScimClient\Settings;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Service\ScimServerService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings {

	public function __construct(
		private readonly ScimServerService $scimServerService,
		private readonly IInitialState $initialStateService,
	) {
	}

	public function getForm() {
		Util::addScript(Application::APP_ID, Application::APP_ID . '-admin-settings');

		$servers = $this->scimServerService->getRegisteredScimServers();

		foreach ($servers as &$server) {
			// Mask API key with dummy secret if set
			if ($server['api_key']) {
				$server['api_key'] = Application::DUMMY_SECRET;
			}
		}

		$this->initialStateService->provideInitialState('admin-server-list', $servers);

		return new TemplateResponse(Application::APP_ID, 'admin-settings');
	}

	public function getSection() {
		return Application::APP_ID;
	}

	public function getPriority() {
		return 90;
	}
}
