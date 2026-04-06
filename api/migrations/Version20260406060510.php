<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406060510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at/updated_at timestamps to all entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE enrollment ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE enrollment ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE instructor ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE instructor ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE module ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE module ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE review ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE student ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE student ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');

        // Retirer les defaults après remplissage des lignes existantes
        $this->addSql('ALTER TABLE course ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE enrollment ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE enrollment ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE instructor ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE instructor ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE module ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE module ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE review ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE student ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE student ALTER updated_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course DROP updated_at');
        $this->addSql('ALTER TABLE enrollment DROP created_at');
        $this->addSql('ALTER TABLE enrollment DROP updated_at');
        $this->addSql('ALTER TABLE instructor DROP created_at');
        $this->addSql('ALTER TABLE instructor DROP updated_at');
        $this->addSql('ALTER TABLE module DROP created_at');
        $this->addSql('ALTER TABLE module DROP updated_at');
        $this->addSql('ALTER TABLE review DROP updated_at');
        $this->addSql('ALTER TABLE student DROP created_at');
        $this->addSql('ALTER TABLE student DROP updated_at');
    }
}
