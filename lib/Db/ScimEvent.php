<?php

declare(strict_types=1);

namespace OCA\ScimClient\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * Class ScimEvent
 *
 * @package OCA\ScimClient\Db
 *
 * @method string getEvent()
 * @method int getServerId()
 * @method string getGroupId()
 * @method string getUserId()
 * @method string getFeature()
 * @method string getValue()
 * @method string getPassword()
 * @method void setEvent(string $event)
 * @method void setServerId(int $serverId)
 * @method void setGroupId(string $groupId)
 * @method void setUserId(string $userId)
 * @method void setFeature(string $feature)
 * @method void setValue(string $value)
 * @method void setPassword(string $password)
 */
class ScimEvent extends Entity implements JsonSerializable {

	protected $event;
	protected $serverId;
	protected $groupId;
	protected $userId;
	protected $feature;
	protected $value;
	protected $password;

	/**
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		$this->addType('event', Types::STRING);
		$this->addType('serverId', Types::BIGINT);
		$this->addType('groupId', Types::STRING);
		$this->addType('userId', Types::STRING);
		$this->addType('feature', Types::STRING);
		$this->addType('value', Types::STRING);
		$this->addType('password', Types::STRING);

		if (isset($params['id'])) {
			$this->setId($params['id']);
		}
		if (isset($params['event'])) {
			$this->setEvent($params['event']);
		}
		if (isset($params['server_id'])) {
			$this->setServerId($params['server_id']);
		}
		if (isset($params['group_id'])) {
			$this->setGroupId($params['group_id']);
		}
		if (isset($params['user_id'])) {
			$this->setUserId($params['user_id']);
		}
		if (isset($params['feature'])) {
			$this->setFeature($params['feature']);
		}
		if (isset($params['value'])) {
			$this->setValue($params['value']);
		}
		if (isset($params['password'])) {
			$this->setPassword($params['password']);
		}
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'event' => $this->getEvent(),
			'server_id' => $this->getServerId(),
			'group_id' => $this->getGroupId(),
			'user_id' => $this->getUserId(),
			'feature' => $this->getFeature(),
			'value' => $this->getValue(),
			'password' => $this->getPassword(),
		];
	}
}
