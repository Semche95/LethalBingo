<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719213058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unique constraint on (number, season) in game_round to allow temporary duplicates.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_f7dd93bb96901f54f0e45ba9');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_f7dd93bb96901f54f0e45ba9 ON game_round (number, season)');
    }
}
