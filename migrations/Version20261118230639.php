<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118230639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link the scoring rules to the rounds they apply to';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE game_round_rule (game_round_id INT NOT NULL, rule_id INT NOT NULL, PRIMARY KEY (game_round_id, rule_id))');
        $this->addSql('CREATE INDEX IDX_DB37A81D44A386 ON game_round_rule (game_round_id)');
        $this->addSql('CREATE INDEX IDX_DB37A81744E0351 ON game_round_rule (rule_id)');
        $this->addSql('ALTER TABLE game_round_rule ADD CONSTRAINT FK_DB37A81D44A386 FOREIGN KEY (game_round_id) REFERENCES game_round (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_round_rule ADD CONSTRAINT FK_DB37A81744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_round_rule DROP CONSTRAINT FK_DB37A81D44A386');
        $this->addSql('ALTER TABLE game_round_rule DROP CONSTRAINT FK_DB37A81744E0351');
        $this->addSql('DROP TABLE game_round_rule');
    }
}
