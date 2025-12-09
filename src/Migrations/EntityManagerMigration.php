<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Migrations;

use Dbp\Relay\CoreBundle\Doctrine\AbstractEntityManagerMigration;

abstract class EntityManagerMigration extends AbstractEntityManagerMigration
{
    protected function getEntityManagerId(): string
    {
        return 'dbp_relay_mono_bundle';
    }
}
