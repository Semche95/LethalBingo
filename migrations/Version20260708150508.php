<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260708150508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE game ADD is_paused BOOLEAN DEFAULT FALSE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE game ADD total_paused_seconds INT DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE game ADD last_paused_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN game.last_paused_at IS '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE game DROP is_paused
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE game DROP total_paused_seconds
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE game DROP last_paused_at
        SQL);
    }
}
