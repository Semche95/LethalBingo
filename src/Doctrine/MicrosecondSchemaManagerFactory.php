<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\SchemaManagerFactory;
use Doctrine\DBAL\Types\Type;

/**
 * Reads a TIMESTAMP(6) column back as a DateTimeMicroImmutableType: PostgreSQL
 * maps every timestamp to the second-precision type, so the schema comparison
 * would otherwise always see a difference on these columns.
 */
final class MicrosecondSchemaManagerFactory implements SchemaManagerFactory
{
    /**
     * @return AbstractSchemaManager<AbstractPlatform>
     */
    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        $platform = $connection->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            return $platform->createSchemaManager($connection);
        }

        return new class($connection, $platform) extends PostgreSQLSchemaManager {
            protected function _getPortableTableColumnDefinition(array $tableColumn): Column
            {
                $column = parent::_getPortableTableColumnDefinition($tableColumn);
                $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

                if ($tableColumn['type'] === 'timestamp' && str_starts_with((string) $tableColumn['complete_type'], 'timestamp(6)')) {
                    $column->setType(Type::getType(DateTimeMicroImmutableType::NAME));
                }

                return $column;
            }
        };
    }
}
