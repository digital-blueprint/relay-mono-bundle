<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220617062247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mono_payments (identifier VARCHAR(36) NOT NULL, type VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL, client_ip VARCHAR(45) DEFAULT NULL, return_url VARCHAR(255) DEFAULT NULL, notify_url VARCHAR(255) DEFAULT NULL, local_identifier VARCHAR(255) DEFAULT NULL, payment_status VARCHAR(255) NOT NULL, payment_reference VARCHAR(255) DEFAULT NULL, amount VARCHAR(8) DEFAULT NULL, currency VARCHAR(3) DEFAULT NULL, alternate_name VARCHAR(3) DEFAULT NULL, honorific_prefix VARCHAR(255) DEFAULT NULL, given_name VARCHAR(255) DEFAULT NULL, family_name VARCHAR(255) DEFAULT NULL, company_name VARCHAR(255) DEFAULT NULL, honorific_suffix VARCHAR(255) DEFAULT NULL, recipient VARCHAR(255) DEFAULT NULL, payment_method VARCHAR(255) DEFAULT NULL, data_protection_declaration_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mono_payments');
    }
}
