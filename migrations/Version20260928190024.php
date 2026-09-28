<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928190024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game manual_adjustment_seconds and store total_paused_seconds with sub-second precision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD manual_adjustment_seconds INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE game ALTER total_paused_seconds TYPE DOUBLE PRECISION');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ALTER total_paused_seconds TYPE INT USING ROUND(total_paused_seconds)::INT');
        $this->addSql('ALTER TABLE game DROP manual_adjustment_seconds');
    }
}
