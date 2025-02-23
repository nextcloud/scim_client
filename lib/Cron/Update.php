<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Cron;

use OCA\ScimClient\Db\ScimEvent;
use OCA\ScimClient\Service\ScimApiService;
use OCA\ScimClient\Service\ScimEventService;
use OCA\ScimClient\Service\ScimServerService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class Update extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		private readonly ScimApiService $scimApiService,
		private readonly ScimEventService $scimEventService,
		private readonly ScimServerService $scimServerService,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);

		// Run every 5 minutes
		$this->setInterval(300);
	}

	protected function run($argument): void {
		$events = $this->scimEventService->getScimEvents();

		if (!$events) {
			return;
		}

		foreach ($events as &$e) {
			$e['success'] = true;
		}

		$servers = $this->scimServerService->getRegisteredScimServers();

		foreach ($servers as $server) {
			$this->scimApiService->updateScimServer($server, $events);
		}

		foreach ($events as $event) {
			if ($event['success']) {
				// Update operation for the event succeeded on all servers, delete it
				$this->scimEventService->deleteScimEvent(new ScimEvent($event));
			}
		}
	}
}
