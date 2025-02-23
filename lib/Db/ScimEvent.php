<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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
 * @method string getGroupId()
 * @method string getUserId()
 * @method string getFeature()
 * @method string getValue()
 * @method void setEvent(string $event)
 * @method void setGroupId(string $groupId)
 * @method void setUserId(string $userId)
 * @method void setFeature(string $feature)
 * @method void setValue(string $value)
 */
class ScimEvent extends Entity implements JsonSerializable {

	protected $event;
	protected $groupId;
	protected $userId;
	protected $feature;
	protected $value;

	/**
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		$this->addType('event', Types::STRING);
		$this->addType('groupId', Types::STRING);
		$this->addType('userId', Types::STRING);
		$this->addType('feature', Types::STRING);
		$this->addType('value', Types::STRING);

		if ($params['id']) {
			$this->setId($params['id']);
		}
		if ($params['event']) {
			$this->setEvent($params['event']);
		}
		if ($params['group_id']) {
			$this->setGroupId($params['group_id']);
		}
		if ($params['user_id']) {
			$this->setUserId($params['user_id']);
		}
		if ($params['feature']) {
			$this->setFeature($params['feature']);
		}
		if ($params['value']) {
			$this->setValue($params['value']);
		}
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'event' => $this->getEvent(),
			'group_id' => $this->getGroupId(),
			'user_id' => $this->getUserId(),
			'feature' => $this->getFeature(),
			'value' => $this->getValue(),
		];
	}
}
