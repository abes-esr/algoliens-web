<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200319202834 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rcr ADD iln_id INT NOT NULL');
        $this->addSql('ALTER TABLE rcr ADD CONSTRAINT FK_58EF4D588002F96F FOREIGN KEY (iln_id) REFERENCES iln (id)');
        $this->addSql('CREATE INDEX IDX_58EF4D588002F96F ON rcr (iln_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rcr DROP FOREIGN KEY FK_58EF4D588002F96F');
        $this->addSql('DROP INDEX IDX_58EF4D588002F96F ON rcr');
        $this->addSql('ALTER TABLE rcr DROP iln_id');
    }
}
