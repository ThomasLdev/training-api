<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406092214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course ALTER level TYPE VARCHAR(12)');
        $this->addSql('ALTER TABLE course ALTER status TYPE VARCHAR(9)');
        $this->addSql('CREATE INDEX course_idx ON course (uuid, title)');
        $this->addSql('CREATE UNIQUE INDEX course_unique_title ON course (title)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX course_idx');
        $this->addSql('DROP INDEX course_unique_title');
        $this->addSql('ALTER TABLE course ALTER level TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE course ALTER status TYPE VARCHAR(20)');
    }
}
