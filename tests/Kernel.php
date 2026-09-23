<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\CoreBundle\TestUtils\CoreTestKernelTrait;
use Dbp\Relay\MonoBundle\DbpRelayMonoBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use CoreTestKernelTrait;

    /** @return iterable<BundleInterface> */
    protected function registerAdditionalBundles(): iterable
    {
        yield new DoctrineBundle();
        yield new DoctrineMigrationsBundle();
        yield new DbpRelayMonoBundle();
    }

    protected function configureAdditionalContainer(ContainerConfigurator $container): void
    {
        $container->services()->set(DummyBackendService::class)->public()->autoconfigure();
        $container->services()->set(DummyPaymentServiceProviderService::class)->public()->autoconfigure();

        $container->extension('dbp_relay_mono', [
            'database_url' => 'sqlite:///:memory:',
        ]);
    }
}
