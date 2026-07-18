<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717230157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make team_member.captain non-nullable with default false';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE team_member SET captain = false WHERE captain IS NULL');
        $this->addSql('ALTER TABLE team_member ALTER captain SET DEFAULT false');
        $this->addSql('ALTER TABLE team_member ALTER captain SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team_member ALTER captain DROP DEFAULT');
        $this->addSql('ALTER TABLE team_member ALTER captain DROP NOT NULL');
    }
}
