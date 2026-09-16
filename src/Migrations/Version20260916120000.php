<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260916120000 extends EntityManagerMigration
{
    public function getDescription(): string
    {
        return 'Clear startedAt for prepared payments so that a prepared status always implies startedAt is null';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE mono_payments SET started_at = NULL WHERE payment_status = 'prepared' AND started_at IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
    }
}
