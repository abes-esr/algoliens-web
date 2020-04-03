<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200403131304 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE batch_import (id INT AUTO_INCREMENT NOT NULL, rcr_id INT NOT NULL, run_date DATETIME NOT NULL, type SMALLINT NOT NULL, INDEX IDX_4EB5A86D718C74C1 (rcr_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE batch_import ADD CONSTRAINT FK_4EB5A86D718C74C1 FOREIGN KEY (rcr_id) REFERENCES rcr (id)');
        $this->addSql('ALTER TABLE record ADD batch_import_id INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME on update CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE record ADD CONSTRAINT FK_9B349F91FB9BECEF FOREIGN KEY (batch_import_id) REFERENCES batch_import (id)');
        $this->addSql('CREATE INDEX IDX_9B349F91FB9BECEF ON record (batch_import_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE record DROP FOREIGN KEY FK_9B349F91FB9BECEF');
        $this->addSql('DROP TABLE batch_import');
        $this->addSql('DROP INDEX IDX_9B349F91FB9BECEF ON record');
        $this->addSql('ALTER TABLE record DROP batch_import_id, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
    }
}
