<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112203921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the timer mode (countdown or stopwatch), the countdown duration, and the pauses of the stopwatch';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD timer_mode VARCHAR(20) DEFAULT \'countdown\' NOT NULL');
        $this->addSql('ALTER TABLE game ADD countdown_duration_seconds INT DEFAULT 5400 NOT NULL');
        $this->addSql('ALTER TABLE game ADD paused_at TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD paused_duration_seconds DOUBLE PRECISION DEFAULT 0 NOT NULL');
        // Games already started were given one hour.
        $this->addSql('UPDATE game SET countdown_duration_seconds = 3600 WHERE started_at IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP timer_mode');
        $this->addSql('ALTER TABLE game DROP countdown_duration_seconds');
        $this->addSql('ALTER TABLE game DROP paused_at');
        $this->addSql('ALTER TABLE game DROP paused_duration_seconds');
    }
}
