<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200321124249 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE link_error (id INT AUTO_INCREMENT NOT NULL, record_id INT NOT NULL, error_text VARCHAR(255) NOT NULL, error_code VARCHAR(10) NOT NULL, INDEX IDX_F266E0AF4DFD750C (record_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE record (id INT AUTO_INCREMENT NOT NULL, rcr_create_id INT NOT NULL, ppn VARCHAR(9) NOT NULL, status SMALLINT DEFAULT 0 NOT NULL, INDEX IDX_9B349F916C56CE6F (rcr_create_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE link_error ADD CONSTRAINT FK_F266E0AF4DFD750C FOREIGN KEY (record_id) REFERENCES record (id)');
        $this->addSql('ALTER TABLE record ADD CONSTRAINT FK_9B349F916C56CE6F FOREIGN KEY (rcr_create_id) REFERENCES rcr (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE link_error DROP FOREIGN KEY FK_F266E0AF4DFD750C');
        $this->addSql('DROP TABLE link_error');
        $this->addSql('DROP TABLE record');
    }
}
