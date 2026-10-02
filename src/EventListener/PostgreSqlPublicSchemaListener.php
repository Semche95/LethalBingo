<?php

declare(strict_types=1);

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

/**
 * The schema built from the mapping never lists PostgreSQL's "public" schema,
 * while the one read from the database does: without this listener every
 * doctrine:migrations:diff produces a migration whose down() runs
 * "CREATE SCHEMA public".
 */
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final class PostgreSqlPublicSchemaListener
{
    public const string SCHEMA = 'public';

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();
        if ($schema->hasNamespace(self::SCHEMA)) {
            return;
        }

        $schema->createNamespace(self::SCHEMA);
    }
}
