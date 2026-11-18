<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113171125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Turn the scoring rules into a measure and a coefficient, and add the default rules';
    }

    public function up(Schema $schema): void
    {
        // The rules had only a label and no screen used them.
        $this->addSql('DELETE FROM rule');
        $this->addSql('ALTER TABLE rule ADD computed_measure VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE rule ADD measure VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE rule ADD coefficient INT NOT NULL');
        $this->addSql('ALTER TABLE rule DROP label');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_46D8ACCC3E699890 ON rule (computed_measure)');
        $this->addSql("INSERT INTO rule (computed_measure, coefficient) VALUES
            ('validated_squares', 100),
            ('four_square_lines', 100),
            ('five_square_lines', 200)");
        $this->addSql("INSERT INTO rule (measure, coefficient) VALUES ('Cases dévoilées à la demande', -50)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM rule');
        $this->addSql('DROP INDEX UNIQ_46D8ACCC3E699890');
        $this->addSql('ALTER TABLE rule ADD label VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE rule DROP computed_measure');
        $this->addSql('ALTER TABLE rule DROP measure');
        $this->addSql('ALTER TABLE rule DROP coefficient');
    }
}
