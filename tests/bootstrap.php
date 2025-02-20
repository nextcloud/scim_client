<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once __DIR__ . '/../../../tests/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

\OC_App::loadApp(OCA\ScimClient\AppInfo\Application::APP_ID);
OC_Hook::clear();
