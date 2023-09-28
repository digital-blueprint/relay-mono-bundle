<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20230928132334 extends EntityManagerMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mono_payments DROP number_of_uses');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mono_payments ADD number_of_uses SMALLINT DEFAULT 0 NOT NULL');
    }
}
