<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Service;

use OCA\ScimClient\Db\ScimEvent;
use OCA\ScimClient\Db\ScimEventMapper;
use OCP\DB\Exception;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class ScimEventService {

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ScimEventMapper $mapper,
		private readonly ICrypto $crypto,
	) {
	}

	public function addScimEvent(array $params): ?ScimEvent {
		try {
			return $this->mapper->insert(new ScimEvent($params));
		} catch (Exception $e) {
			$this->logger->error('Failed to create SCIM event. Error: ' . $e->getMessage(), ['exception' => $e]);
			return null;
		}
	}

	public function deleteScimEvent(ScimEvent $event): ?ScimEvent {
		try {
			return $this->mapper->delete($event);
		} catch (Exception $e) {
			$this->logger->error('Failed to delete SCIM event. Error: ' . $e->getMessage(), ['exception' => $e]);
			return null;
		}
	}

	public function getScimEvents(): array {
		try {
			return array_map(static fn (ScimEvent $s): array => $s->jsonSerialize(), $this->mapper->findAll());
		} catch (Exception $e) {
			$this->logger->debug('Failed to get SCIM events. Error: ' . $e->getMessage(), ['exception' => $e]);
			return [];
		}
	}
}
