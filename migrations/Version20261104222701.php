<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104222701 extends AbstractMigration
{
    private const string IN_PROGRESS = 'started_at IS NOT NULL AND finished_at IS NULL';

    public function getDescription(): string
    {
        return 'Store the moment the countdown reaches zero instead of the paused and adjusted seconds';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD countdown_zero_at TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD paused_remaining_seconds DOUBLE PRECISION DEFAULT 0 NOT NULL');

        // Running: the old remaining time 3600 - (now - started_at - total_paused_seconds)
        // + manual_adjustment_seconds reaches zero at this moment.
        $this->addSql('UPDATE game SET countdown_zero_at = started_at + make_interval(secs => 3600 + total_paused_seconds + manual_adjustment_seconds) WHERE ' . self::IN_PROGRESS . ' AND NOT is_paused');
        // Paused: the old remaining time frozen at last_paused_at.
        $this->addSql('UPDATE game SET paused_remaining_seconds = GREATEST(0, 3600 - (EXTRACT(EPOCH FROM COALESCE(last_paused_at, LOCALTIMESTAMP) - started_at) - total_paused_seconds) + manual_adjustment_seconds) WHERE ' . self::IN_PROGRESS . ' AND is_paused');

        $this->addSql('ALTER TABLE game DROP total_paused_seconds');
        $this->addSql('ALTER TABLE game DROP manual_adjustment_seconds');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD total_paused_seconds DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE game ADD manual_adjustment_seconds INT DEFAULT 0 NOT NULL');

        // The whole shift of the countdown goes back into total_paused_seconds.
        $this->addSql('UPDATE game SET total_paused_seconds = EXTRACT(EPOCH FROM countdown_zero_at - started_at) - 3600 WHERE ' . self::IN_PROGRESS . ' AND NOT is_paused AND countdown_zero_at IS NOT NULL');
        $this->addSql('UPDATE game SET last_paused_at = COALESCE(last_paused_at, LOCALTIMESTAMP) WHERE ' . self::IN_PROGRESS . ' AND is_paused');
        $this->addSql('UPDATE game SET total_paused_seconds = paused_remaining_seconds - 3600 + EXTRACT(EPOCH FROM last_paused_at - started_at) WHERE ' . self::IN_PROGRESS . ' AND is_paused');
        $this->addSql('ALTER TABLE game ALTER total_paused_seconds DROP DEFAULT');

        $this->addSql('ALTER TABLE game DROP countdown_zero_at');
        $this->addSql('ALTER TABLE game DROP paused_remaining_seconds');
    }
}
