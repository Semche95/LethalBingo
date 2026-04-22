<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415220646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la base de données';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE bingo_square_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE bingo_square_category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE bingo_square_validated_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE game_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE game_round_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE team_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE team_member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE bingo_square (id INT NOT NULL, round_id INT NOT NULL, category_id INT NOT NULL, label TEXT NOT NULL, row INT NOT NULL, col INT NOT NULL, visible BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C37EA6A8A6005CA0 ON bingo_square (round_id)');
        $this->addSql('CREATE INDEX IDX_C37EA6A812469DE2 ON bingo_square (category_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C37EA6A8A6005CA0EA750E8 ON bingo_square (round_id, label)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C37EA6A8A6005CA08430F6DB13B1F670 ON bingo_square (round_id, row, col)');
        $this->addSql('CREATE TABLE bingo_square_category (id INT NOT NULL, label TEXT NOT NULL, position INT DEFAULT 0 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_30F95532EA750E8 ON bingo_square_category (label)');
        $this->addSql('CREATE TABLE bingo_square_validated (id INT NOT NULL, game_id INT NOT NULL, bingo_square_id INT NOT NULL, validated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D01BDBF9E48FD905 ON bingo_square_validated (game_id)');
        $this->addSql('CREATE INDEX IDX_D01BDBF9FECA8E7F ON bingo_square_validated (bingo_square_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D01BDBF9E48FD905FECA8E7F ON bingo_square_validated (game_id, bingo_square_id)');
        $this->addSql('COMMENT ON COLUMN bingo_square_validated.validated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE game (id INT NOT NULL, team_id INT NOT NULL, round_id INT NOT NULL, slug VARCHAR(255) NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_232B318C296CD8AE ON game (team_id)');
        $this->addSql('CREATE INDEX IDX_232B318CA6005CA0 ON game (round_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C989D9B62 ON game (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C296CD8AEA6005CA0 ON game (team_id, round_id)');
        $this->addSql('COMMENT ON COLUMN game.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game.finished_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE bingo_square_revealed (game_id INT NOT NULL, bingo_square_id INT NOT NULL, PRIMARY KEY(game_id, bingo_square_id))');
        $this->addSql('CREATE INDEX IDX_78C7E855E48FD905 ON bingo_square_revealed (game_id)');
        $this->addSql('CREATE INDEX IDX_78C7E855FECA8E7F ON bingo_square_revealed (bingo_square_id)');
        $this->addSql('CREATE TABLE game_round (id INT NOT NULL, number INT NOT NULL, season INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F7DD93BB96901F54F0E45BA9 ON game_round (number, season)');
        $this->addSql('CREATE TABLE team (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4E0A61F5E237E06 ON team (name)');
        $this->addSql('CREATE TABLE team_member (id INT NOT NULL, team_id INT NOT NULL, name VARCHAR(255) NOT NULL, captain BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6FFBDA1296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6FFBDA1296CD8AE5E237E06 ON team_member (team_id, name)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
        $this->addSql('ALTER TABLE bingo_square ADD CONSTRAINT FK_C37EA6A8A6005CA0 FOREIGN KEY (round_id) REFERENCES game_round (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bingo_square ADD CONSTRAINT FK_C37EA6A812469DE2 FOREIGN KEY (category_id) REFERENCES bingo_square_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bingo_square_validated ADD CONSTRAINT FK_D01BDBF9E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bingo_square_validated ADD CONSTRAINT FK_D01BDBF9FECA8E7F FOREIGN KEY (bingo_square_id) REFERENCES bingo_square (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CA6005CA0 FOREIGN KEY (round_id) REFERENCES game_round (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bingo_square_revealed ADD CONSTRAINT FK_78C7E855E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bingo_square_revealed ADD CONSTRAINT FK_78C7E855FECA8E7F FOREIGN KEY (bingo_square_id) REFERENCES bingo_square (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE bingo_square_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE bingo_square_category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE bingo_square_validated_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE game_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE game_round_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE team_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE team_member_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE bingo_square DROP CONSTRAINT FK_C37EA6A8A6005CA0');
        $this->addSql('ALTER TABLE bingo_square DROP CONSTRAINT FK_C37EA6A812469DE2');
        $this->addSql('ALTER TABLE bingo_square_validated DROP CONSTRAINT FK_D01BDBF9E48FD905');
        $this->addSql('ALTER TABLE bingo_square_validated DROP CONSTRAINT FK_D01BDBF9FECA8E7F');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318C296CD8AE');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CA6005CA0');
        $this->addSql('ALTER TABLE bingo_square_revealed DROP CONSTRAINT FK_78C7E855E48FD905');
        $this->addSql('ALTER TABLE bingo_square_revealed DROP CONSTRAINT FK_78C7E855FECA8E7F');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA1296CD8AE');
        $this->addSql('DROP TABLE bingo_square');
        $this->addSql('DROP TABLE bingo_square_category');
        $this->addSql('DROP TABLE bingo_square_validated');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE bingo_square_revealed');
        $this->addSql('DROP TABLE game_round');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('DROP TABLE "user"');
    }
}
