<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[CreateTable(
	table: 'scim_client_events',
	description: 'Table used by the SCIM client app to store user/group events for sync operations',
)]
class Version000101Date20250108010942 extends SimpleMigrationStep {

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

		if (!$schema->hasTable('scim_client_events')) {
			$table = $schema->createTable('scim_client_events');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('event', Types::STRING, [
				'notnull' => true,
				'length' => 256,
			]);
			$table->addColumn('server_id', Types::BIGINT, [
				'notnull' => false,
				'unsigned' => true,
			]);
			$table->addColumn('group_id', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('feature', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('value', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('password', Types::STRING, [
				'notnull' => false,
				'length' => 512,
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
