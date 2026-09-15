<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260915120000 extends EntityManagerMigration
{
    public function getDescription(): string
    {
        return 'Convert the removed started payment status to prepared';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE mono_payments SET payment_status = 'prepared' WHERE payment_status = 'started'");
    }

    public function down(Schema $schema): void
    {
    }
}
