<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntityManagerMigration extends AbstractMigration implements ContainerAwareInterface
{
    private const EM_NAME = 'dbp_relay_mono_bundle';

    /** @var ContainerInterface */
    protected $container;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function preUp(Schema $schema): void
    {
        $this->skipInvalidDB();
    }

    public function preDown(Schema $schema): void
    {
        $this->skipInvalidDB();
    }

    private function getEntityManager(): EntityManager
    {
        $name = self::EM_NAME;
        $res = $this->container->get("doctrine.orm.{$name}_entity_manager");
        assert($res instanceof EntityManager);

        return $res;
    }

    private function skipInvalidDB()
    {
        $em = self::EM_NAME;
        $this->skipIf(!($this->platform instanceof AbstractMySQLPlatform), 'Wrong DB platform');
        $this->skipIf($this->connection !== $this->getEntityManager()->getConnection(), "Migration can't be executed on this connection, use --em={$em} to select the right one.'");
    }
}
