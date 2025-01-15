<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\ScimClient\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[DropColumn(
	table: 'scim_client_events',
	name: 'server_id',
)]
#[DropColumn(
	table: 'scim_client_events',
	name: 'password',
)]
#[CreateTable(
	table: 'scim_client_sync',
	description: 'Table used by the SCIM client app to store full sync requests',
)]
class Version000102Date20250115112155 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('scim_client_events')) {
			$table = $schema->getTable('scim_client_events');
			$table->dropColumn('server_id');
			$table->dropColumn('password');
		}

		if (!$schema->hasTable('scim_client_sync')) {
			$table = $schema->createTable('scim_client_sync');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('server_id', Types::BIGINT, [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
