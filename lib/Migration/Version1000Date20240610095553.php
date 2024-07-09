<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 FIXME Your name <your@email.com>
 *
 * FIXME @author Your name <your@email.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Rolls\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * FIXME Auto-generated migration step: Please modify to your needs!
 */
class Version1000Date20240610095553 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		
		$VIDEO_TABLE = 'rolls_videos';
		if (!$schema->hasTable($VIDEO_TABLE)) {
			$table = $schema->createTable($VIDEO_TABLE);

			$table->addColumn('id', \OCP\DB\Types::INTEGER, [
				'notnull' => true,
				'autoincrement' => true
			]);

			$table->addColumn('uuid', \OCP\DB\Types::TEXT, [
				'notnull' => true,
			]);
			
			$table->addColumn('video_file', \OCP\DB\Types::INTEGER, [
				'notnull' => true,
			]);

			$table->addColumn('video_folder', \OCP\DB\Types::INTEGER, [
				'notnull' => true,
			]);

			$table->addColumn('owner', \OCP\DB\Types::TEXT, [
				'notnull' => true,
			]);
			
			$table->addColumn('created_at', \OCP\DB\Types::INTEGER, [
				'notnull' => true,
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
