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

		$serverList = $this->scimServerService->getRegisteredScimServers();
		$this->initialStateService->provideInitialState('admin-server-list', $serverList);

		return new TemplateResponse(Application::APP_ID, 'admin-settings');
	}

	public function getSection() {
		return Application::APP_ID;
	}

	public function getPriority() {
		return 90;
	}
}
