<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105095920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the start of the current pause, no longer read since the remaining time is frozen during a pause';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP last_paused_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD last_paused_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }
}
