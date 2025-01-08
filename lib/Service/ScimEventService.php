<?php

declare(strict_types=1);

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
		if (!empty($params['password'])) {
			$params['password'] = $this->crypto->encrypt($params['password']);
		}

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
			return array_map(function (ScimEvent $s): array {
				$event = $s->jsonSerialize();

				// Decrypt password if set
				if (!empty($event['password'])) {
					$event['password'] = $this->crypto->decrypt($event['password']);
				}

				return $event;
			}, $this->mapper->findAll());
		} catch (Exception $e) {
			$this->logger->debug('Failed to get SCIM events. Error: ' . $e->getMessage(), ['exception' => $e]);
			return [];
		}
	}
}
