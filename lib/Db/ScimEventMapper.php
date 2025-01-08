<?php

declare(strict_types=1);

namespace OCA\ScimClient\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<ScimEvent>
 */
class ScimEventMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'scim_client_events');
	}

	/**
	 * @throws Exception
	 */
	public function findAll(?int $limit = null, ?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->setMaxResults($limit)
			->setFirstResult($offset);
		return $this->findEntities($qb);
	}
}
