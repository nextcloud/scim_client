<?php

declare(strict_types=1);

namespace OCA\ScimClient\Cron;

use OCA\ScimClient\Db\ScimSyncRequest;
use OCA\ScimClient\Service\ScimApiService;
use OCA\ScimClient\Service\ScimServerService;
use OCA\ScimClient\Service\ScimSyncRequestService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class Sync extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		private readonly ScimApiService $scimApiService,
		private readonly ScimSyncRequestService $scimSyncRequestService,
		private readonly ScimServerService $scimServerService,
	) {
		parent::__construct($time);

		// Run as often as possible
		$this->setInterval(1);
	}

	protected function run($argument): void {
		$requests = $this->scimSyncRequestService->getScimSyncRequests();

		foreach ($requests as $request) {
			$server = $this->scimServerService->getScimServer($request['server_id']);

			if ($server) {
				$this->scimApiService->syncScimServer($server->jsonSerialize());
			}

			// TODO: keep the event instead if the sync operation is unsuccessful, write error to server log
			$this->scimSyncRequestService->deleteScimSyncRequest(new ScimSyncRequest($request));
		}
	}
}
