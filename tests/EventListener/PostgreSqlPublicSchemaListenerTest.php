<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\PostgreSqlPublicSchemaListener;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use PHPUnit\Framework\TestCase;

class PostgreSqlPublicSchemaListenerTest extends TestCase
{
    public function testAddsThePublicSchema(): void
    {
        $schema = new Schema();

        new PostgreSqlPublicSchemaListener()->postGenerateSchema($this->createEvent($schema));

        $this->assertTrue($schema->hasNamespace('public'));
    }

    public function testKeepsAnAlreadyDeclaredPublicSchema(): void
    {
        $schema = new Schema();
        $schema->createNamespace('public');

        new PostgreSqlPublicSchemaListener()->postGenerateSchema($this->createEvent($schema));

        $this->assertSame(['public' => 'public'], $schema->getNamespaces());
    }

    private function createEvent(Schema $schema): GenerateSchemaEventArgs
    {
        return new GenerateSchemaEventArgs($this->createStub(EntityManagerInterface::class), $schema);
    }
}
