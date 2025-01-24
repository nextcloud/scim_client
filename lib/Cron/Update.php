<?php

declare(strict_types=1);

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

		$servers = $this->scimServerService->getRegisteredScimServers();

		foreach ($servers as $server) {
			$this->scimApiService->updateScimServer($server, $events);
		}

		// Cleanup processed update events
		// TODO: keep the event instead if the corresponding operation is unsuccessful for at least one server, write error to server log
		foreach ($events as $event) {
			$this->scimEventService->deleteScimEvent(new ScimEvent($event));
		}
	}
}
