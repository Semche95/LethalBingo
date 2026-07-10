<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260711005036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rule (id SERIAL NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('SELECT setval(\'bingo_square_id_seq\', (SELECT MAX(id) FROM bingo_square))');
        $this->addSql('ALTER TABLE bingo_square ALTER id SET DEFAULT nextval(\'bingo_square_id_seq\')');
        $this->addSql('SELECT setval(\'bingo_square_category_id_seq\', (SELECT MAX(id) FROM bingo_square_category))');
        $this->addSql('ALTER TABLE bingo_square_category ALTER id SET DEFAULT nextval(\'bingo_square_category_id_seq\')');
        $this->addSql('ALTER TABLE bingo_square_revealed DROP CONSTRAINT FK_78C7E855E48FD905');
        $this->addSql('ALTER TABLE bingo_square_revealed DROP CONSTRAINT FK_78C7E855FECA8E7F');
        $this->addSql('SELECT setval(\'bingo_square_revealed_id_seq\', (SELECT MAX(id) FROM bingo_square_revealed))');
        $this->addSql('ALTER TABLE bingo_square_revealed ALTER id SET DEFAULT nextval(\'bingo_square_revealed_id_seq\')');
        $this->addSql('ALTER TABLE bingo_square_revealed ADD CONSTRAINT FK_78C7E855E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bingo_square_revealed ADD CONSTRAINT FK_78C7E855FECA8E7F FOREIGN KEY (bingo_square_id) REFERENCES bingo_square (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('SELECT setval(\'bingo_square_validated_id_seq\', (SELECT MAX(id) FROM bingo_square_validated))');
        $this->addSql('ALTER TABLE bingo_square_validated ALTER id SET DEFAULT nextval(\'bingo_square_validated_id_seq\')');
        $this->addSql('SELECT setval(\'game_id_seq\', (SELECT MAX(id) FROM game))');
        $this->addSql('ALTER TABLE game ALTER id SET DEFAULT nextval(\'game_id_seq\')');
        $this->addSql('ALTER TABLE game ALTER is_paused DROP DEFAULT');
        $this->addSql('ALTER TABLE game ALTER total_paused_seconds DROP DEFAULT');
        $this->addSql('SELECT setval(\'game_round_id_seq\', (SELECT MAX(id) FROM game_round))');
        $this->addSql('ALTER TABLE game_round ALTER id SET DEFAULT nextval(\'game_round_id_seq\')');
        $this->addSql('SELECT setval(\'team_id_seq\', (SELECT MAX(id) FROM team))');
        $this->addSql('ALTER TABLE team ALTER id SET DEFAULT nextval(\'team_id_seq\')');
        $this->addSql('SELECT setval(\'team_member_id_seq\', (SELECT MAX(id) FROM team_member))');
        $this->addSql('ALTER TABLE team_member ALTER id SET DEFAULT nextval(\'team_member_id_seq\')');
        $this->addSql('SELECT setval(\'user_id_seq\', (SELECT MAX(id) FROM "user"))');
        $this->addSql('ALTER TABLE "user" ALTER id SET DEFAULT nextval(\'user_id_seq\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE rule');
        $this->addSql('ALTER TABLE bingo_square ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE bingo_square_revealed DROP CONSTRAINT fk_78c7e855e48fd905');
        $this->addSql('ALTER TABLE bingo_square_revealed DROP CONSTRAINT fk_78c7e855feca8e7f');
        $this->addSql('ALTER TABLE bingo_square_revealed ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE bingo_square_revealed ADD CONSTRAINT fk_78c7e855e48fd905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bingo_square_revealed ADD CONSTRAINT fk_78c7e855feca8e7f FOREIGN KEY (bingo_square_id) REFERENCES bingo_square (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE bingo_square_validated ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE bingo_square_category ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE team_member ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE game_round ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE game ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE game ALTER is_paused SET DEFAULT false');
        $this->addSql('ALTER TABLE game ALTER total_paused_seconds SET DEFAULT 0');
    }
}
