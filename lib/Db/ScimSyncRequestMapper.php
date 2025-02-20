<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<ScimSyncRequest>
 */
class ScimSyncRequestMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'scim_client_sync');
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


	/**
	 * @param int $serverId
	 *
	 * @throws Exception
	 */
	public function findAllByServerId(int $serverId, ?int $limit = null, ?int $offset = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where(
				$qb->expr()->eq('server_id', $qb->createNamedParameter($serverId, IQueryBuilder::PARAM_INT))
			)
			->setMaxResults($limit)
			->setFirstResult($offset);
		return $this->findEntities($qb);
	}
}
