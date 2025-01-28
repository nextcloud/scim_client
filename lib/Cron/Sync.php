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
				$results = $this->scimApiService->syncScimServer($server->jsonSerialize());
				$syncSuccessful = array_reduce($results, fn (bool $prev, array $result): bool => $prev && $result['success'], true);

				if (!$syncSuccessful) {
					// At least one operation has failed, do not delete sync request event
					continue;
				}
			}

			$this->scimSyncRequestService->deleteScimSyncRequest(new ScimSyncRequest($request));
		}
	}
}
