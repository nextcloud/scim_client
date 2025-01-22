<?php

declare(strict_types=1);

namespace OCA\ScimClient\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * Class ScimServer
 *
 * @package OCA\ScimClient\Db
 *
 * @method string getName()
 * @method string getUrl()
 * @method string getApiKey()
 * @method void setName(string $name)
 * @method void setUrl(string $url)
 * @method void setApiKey(string $apiKey)
 */
class ScimServer extends Entity implements JsonSerializable {

	protected $name;
	protected $url;
	protected $apiKey;

	/**
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		$this->addType('name', Types::STRING);
		$this->addType('url', Types::STRING);
		$this->addType('apiKey', Types::STRING);

		if ($params['id']) {
			$this->setId($params['id']);
		}
		if ($params['name']) {
			$this->setName($params['name']);
		}
		if ($params['url']) {
			$this->setUrl($params['url']);
		}
		if ($params['api_key']) {
			$this->setApiKey($params['api_key']);
		}
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'url' => $this->getUrl(),
			'api_key' => $this->getApiKey(),
		];
	}
}
