<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416175336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des catégories pour les cases de bingo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO bingo_square_category (id, label, position) VALUES (1, \'pendant la game\', 3);');
        $this->addSql('INSERT INTO bingo_square_category (id, label, position) VALUES (2, \'morts\', 5);');
        $this->addSql('INSERT INTO bingo_square_category (id, label, position) VALUES (3, \'lancement du vaisseau\', 1);');
        $this->addSql('INSERT INTO bingo_square_category (id, label, position) VALUES (4, \'autre\', 4);');
        $this->addSql('INSERT INTO bingo_square_category (id, label, position) VALUES (5, \'the company\', 2);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE bingo_square_category');
    }
}
