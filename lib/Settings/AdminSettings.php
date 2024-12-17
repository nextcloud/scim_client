<?php

declare(strict_types=1);

namespace OCA\ScimClient\Settings;

use OCA\ScimClient\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings {

	public function __construct() {
	}

	public function getForm() {
		Util::addScript(Application::APP_ID, Application::APP_ID . '-admin-settings');

		return new TemplateResponse(Application::APP_ID, 'admin-settings');
	}

	public function getSection() {
		return Application::APP_ID;
	}

	public function getPriority() {
		return 90;
	}
}
