<?php

declare(strict_types=1);

namespace OCA\ScimClient\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * Class ScimSyncRequest
 *
 * @package OCA\ScimClient\Db
 *
 * @method string getServerId()
 * @method void setServerId(int $serverId)
 */
class ScimSyncRequest extends Entity implements JsonSerializable {

	protected $serverId;

	/**
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		$this->addType('serverId', Types::BIGINT);

		if (isset($params['id'])) {
			$this->setId($params['id']);
		}
		if (isset($params['server_id'])) {
			$this->setServerId($params['server_id']);
		}
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'server_id' => $this->getServerId(),
		];
	}
}
