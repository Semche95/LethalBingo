<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260725170933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_c37ea6a8a6005ca0ea750e8');
        $this->addSql("CREATE UNIQUE INDEX uniq_bingo_square_round_label_non_empty ON bingo_square (round_id, label) WHERE label != ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_bingo_square_round_label_non_empty');
        $this->addSql('CREATE UNIQUE INDEX uniq_c37ea6a8a6005ca0ea750e8 ON bingo_square (round_id, label)');
    }
}
