<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200320145154 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE link_error (id INT AUTO_INCREMENT NOT NULL, iln_create_id INT NOT NULL, rcr_create_id INT NOT NULL, rcr_update_id INT NOT NULL, ppn VARCHAR(9) NOT NULL, type_doc VARCHAR(255) NOT NULL, text_error VARCHAR(255) NOT NULL, date_update DATE NOT NULL, code_error VARCHAR(10) NOT NULL, type_doc_label VARCHAR(255) NOT NULL, INDEX IDX_F266E0AFE9E01E6B (iln_create_id), INDEX IDX_F266E0AF6C56CE6F (rcr_create_id), INDEX IDX_F266E0AF57DEF95D (rcr_update_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE link_error ADD CONSTRAINT FK_F266E0AFE9E01E6B FOREIGN KEY (iln_create_id) REFERENCES iln (id)');
        $this->addSql('ALTER TABLE link_error ADD CONSTRAINT FK_F266E0AF6C56CE6F FOREIGN KEY (rcr_create_id) REFERENCES rcr (id)');
        $this->addSql('ALTER TABLE link_error ADD CONSTRAINT FK_F266E0AF57DEF95D FOREIGN KEY (rcr_update_id) REFERENCES rcr (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE link_error');
    }
}
