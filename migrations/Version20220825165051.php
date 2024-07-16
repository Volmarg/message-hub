<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220825165051 extends AbstractMigration
{
    /**
     * Fix: "There is no active transaction" on success
     *
     * @return bool
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mail_account (id INT AUTO_INCREMENT NOT NULL, client VARCHAR(100) NOT NULL, login VARCHAR(100) NOT NULL, password VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, host VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_A78BD7CB5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_attachment (id INT AUTO_INCREMENT NOT NULL, email_id INT NOT NULL, created DATETIME NOT NULL, path VARCHAR(255) NOT NULL, file_name VARCHAR(255) NOT NULL, file_type VARCHAR(50) NOT NULL, INDEX IDX_AD9C3347A832C1C9 (email_id), UNIQUE INDEX unique_file_name (file_name, email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_open_state (id INT AUTO_INCREMENT NOT NULL, email_id INT NOT NULL, created DATETIME NOT NULL, open TINYINT(1) NOT NULL, opening_token VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_3CED1801A832C1C9 (email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mail_attachment ADD CONSTRAINT FK_AD9C3347A832C1C9 FOREIGN KEY (email_id) REFERENCES mail (id)');
        $this->addSql('ALTER TABLE mail_open_state ADD CONSTRAINT FK_3CED1801A832C1C9 FOREIGN KEY (email_id) REFERENCES mail (id)');
        $this->addSql('ALTER TABLE discord_message ADD sending_error LONGTEXT DEFAULT NULL, ADD re_sending_error LONGTEXT DEFAULT NULL, ADD send_to_error LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE discord_webhook ADD created DATETIME DEFAULT NULL, CHANGE deleted deleted TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE mail ADD account_id INT DEFAULT NULL, ADD parsed_body LONGTEXT DEFAULT NULL, ADD type VARCHAR(50) NOT NULL, ADD sending_error LONGTEXT DEFAULT NULL, ADD re_sending_error LONGTEXT DEFAULT NULL, ADD send_to_error LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE mail ADD CONSTRAINT FK_5126AC489B6B5FBA FOREIGN KEY (account_id) REFERENCES mail_account (id)');
        $this->addSql('CREATE INDEX IDX_5126AC489B6B5FBA ON mail (account_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mail DROP FOREIGN KEY FK_5126AC489B6B5FBA');
        $this->addSql('DROP TABLE mail_account');
        $this->addSql('DROP TABLE mail_attachment');
        $this->addSql('DROP TABLE mail_open_state');
        $this->addSql('ALTER TABLE discord_message DROP sending_error, DROP re_sending_error, DROP send_to_error');
        $this->addSql('ALTER TABLE discord_webhook DROP created, CHANGE deleted deleted TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('DROP INDEX IDX_5126AC489B6B5FBA ON mail');
        $this->addSql('ALTER TABLE mail DROP account_id, DROP parsed_body, DROP type, DROP sending_error, DROP re_sending_error, DROP send_to_error');
    }
}
