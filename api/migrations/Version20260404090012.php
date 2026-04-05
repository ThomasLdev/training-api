<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404090012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course ADD uuid UUID NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_169E6FB9D17F50A6 ON course (uuid)');
        $this->addSql('ALTER TABLE enrollment ADD uuid UUID NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DBDCD7E1D17F50A6 ON enrollment (uuid)');
        $this->addSql('ALTER TABLE instructor ADD uuid UUID NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31FC43DDD17F50A6 ON instructor (uuid)');
        $this->addSql('ALTER TABLE review ADD uuid UUID NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_794381C6D17F50A6 ON review (uuid)');
        $this->addSql('ALTER TABLE student ADD uuid UUID NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B723AF33D17F50A6 ON student (uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_169E6FB9D17F50A6');
        $this->addSql('ALTER TABLE course DROP uuid');
        $this->addSql('DROP INDEX UNIQ_DBDCD7E1D17F50A6');
        $this->addSql('ALTER TABLE enrollment DROP uuid');
        $this->addSql('DROP INDEX UNIQ_31FC43DDD17F50A6');
        $this->addSql('ALTER TABLE instructor DROP uuid');
        $this->addSql('DROP INDEX UNIQ_794381C6D17F50A6');
        $this->addSql('ALTER TABLE review DROP uuid');
        $this->addSql('DROP INDEX UNIQ_B723AF33D17F50A6');
        $this->addSql('ALTER TABLE student DROP uuid');
    }
}
