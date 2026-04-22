<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add id and revealedAt fields to bingo_square_revealed table';
    }

    public function up(Schema $schema): void
    {
        // Create a sequence for the id column
        $this->addSql('CREATE SEQUENCE bingo_square_revealed_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        
        // Add id column
        $this->addSql('ALTER TABLE bingo_square_revealed ADD id INT NOT NULL DEFAULT nextval(\'bingo_square_revealed_id_seq\')');
        
        // Add revealedAt column
        $this->addSql('ALTER TABLE bingo_square_revealed ADD revealed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('COMMENT ON COLUMN bingo_square_revealed.revealed_at IS \'(DC2Type:datetime_immutable)\'');
        
        // Change primary key from (game_id, bingo_square_id) to id
        $this->addSql('ALTER TABLE bingo_square_revealed DROP CONSTRAINT bingo_square_revealed_pkey');
        $this->addSql('ALTER TABLE bingo_square_revealed ADD PRIMARY KEY (id)');
        
        // Add unique constraint on (game_id, bingo_square_id)
        $this->addSql('CREATE UNIQUE INDEX UNIQ_78C7E855E48FD905FECA8E7F ON bingo_square_revealed (game_id, bingo_square_id)');
        
        // Remove default values after data is migrated
        $this->addSql('ALTER TABLE bingo_square_revealed ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE bingo_square_revealed ALTER revealed_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // Remove unique constraint
        $this->addSql('DROP INDEX UNIQ_78C7E855E48FD905FECA8E7F');
        
        // Change primary key back to (game_id, bingo_square_id)
        $this->addSql('ALTER TABLE bingo_square_revealed DROP CONSTRAINT bingo_square_revealed_pkey');
        $this->addSql('ALTER TABLE bingo_square_revealed ADD PRIMARY KEY (game_id, bingo_square_id)');
        
        // Remove revealedAt column
        $this->addSql('ALTER TABLE bingo_square_revealed DROP revealed_at');
        
        // Remove id column
        $this->addSql('ALTER TABLE bingo_square_revealed DROP id');
        
        // Drop the sequence
        $this->addSql('DROP SEQUENCE bingo_square_revealed_id_seq CASCADE');
    }
}
