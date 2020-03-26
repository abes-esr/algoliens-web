<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200323130454 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE paprika_link DROP FOREIGN KEY FK_CECF012F4DFD750C');
        $this->addSql('DROP INDEX IDX_CECF012F4DFD750C ON paprika_link');
        $this->addSql('ALTER TABLE paprika_link CHANGE record_id link_error_id INT NOT NULL');
        $this->addSql('ALTER TABLE paprika_link ADD CONSTRAINT FK_CECF012F4ECAE81E FOREIGN KEY (link_error_id) REFERENCES link_error (id)');
        $this->addSql('CREATE INDEX IDX_CECF012F4ECAE81E ON paprika_link (link_error_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE paprika_link DROP FOREIGN KEY FK_CECF012F4ECAE81E');
        $this->addSql('DROP INDEX IDX_CECF012F4ECAE81E ON paprika_link');
        $this->addSql('ALTER TABLE paprika_link CHANGE link_error_id record_id INT NOT NULL');
        $this->addSql('ALTER TABLE paprika_link ADD CONSTRAINT FK_CECF012F4DFD750C FOREIGN KEY (record_id) REFERENCES record (id)');
        $this->addSql('CREATE INDEX IDX_CECF012F4DFD750C ON paprika_link (record_id)');
    }
}
