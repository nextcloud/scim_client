<?php

declare(strict_types=1);

namespace OCA\ScimClient\Service;

use OCA\ScimClient\Db\ScimSyncRequest;
use OCA\ScimClient\Db\ScimSyncRequestMapper;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class ScimSyncRequestService {

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ScimSyncRequestMapper $mapper,
	) {
	}

	public function addScimSyncRequest(array $params): ?ScimSyncRequest {
		try {
			$requests = $this->mapper->findAllByServerId($params['server_id']);
			$request = array_shift($requests);

			// Delete any duplicate requests in database
			foreach ($requests as $duplicateRequest) {
				$this->mapper->delete($duplicateRequest);
			}

			return isset($request) ? $request : $this->mapper->insert(new ScimSyncRequest($params));
		} catch (Exception $e) {
			$this->logger->error('Failed to create SCIM sync request. Error: ' . $e->getMessage(), ['exception' => $e]);
			return null;
		}
	}

	public function deleteScimSyncRequest(ScimSyncRequest $request): ?ScimSyncRequest {
		try {
			return $this->mapper->delete($request);
		} catch (Exception $e) {
			$this->logger->error('Failed to delete SCIM sync request. Error: ' . $e->getMessage(), ['exception' => $e]);
			return null;
		}
	}

	public function getScimSyncRequests(): array {
		try {
			return array_map(static fn (ScimSyncRequest $s): array => $s->jsonSerialize(), $this->mapper->findAll());
		} catch (Exception $e) {
			$this->logger->debug('Failed to get SCIM sync requests. Error: ' . $e->getMessage(), ['exception' => $e]);
			return [];
		}
	}
}
