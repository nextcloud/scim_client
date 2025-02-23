<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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

			return $request ?: $this->mapper->insert(new ScimSyncRequest($params));
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

	public function deleteScimSyncRequestsByServerId(int $serverId): array {
		try {
			return array_map([$this->mapper, 'delete'], $this->mapper->findAllByServerId($serverId));
		} catch (Exception $e) {
			$this->logger->error('Failed to delete SCIM sync requests by server ID. Error: ' . $e->getMessage(), ['exception' => $e]);
			return [];
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
