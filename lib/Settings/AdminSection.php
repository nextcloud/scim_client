<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Settings;

use OCA\ScimClient\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {

	public function __construct(
		private readonly IL10N $l,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	public function getID() {
		return Application::APP_ID;
	}

	public function getName() {
		return $this->l->t('Identity Management');
	}

	public function getPriority() {
		return 75;
	}

	public function getIcon() {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg');
	}
}
