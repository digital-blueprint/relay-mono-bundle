<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Dbp\Relay\CoreBundle\DbpRelayCoreBundle;
use Dbp\Relay\MonoBundle\DbpRelayMonoBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new TwigBundle();
        yield new NelmioCorsBundle();
        yield new MonologBundle();
        yield new ApiPlatformBundle();
        yield new DoctrineBundle();
        yield new DoctrineMigrationsBundle();
        yield new DbpRelayMonoBundle();
        yield new DbpRelayCoreBundle();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@DbpRelayCoreBundle/Resources/config/routing.yaml');
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->services()->set(DummyBackendService::class)->public()->autoconfigure();
        $container->services()->set(DummyPaymentServiceProviderService::class)->public()->autoconfigure();

        $container->import('@DbpRelayCoreBundle/Resources/config/services_test.yaml');
        $container->extension('framework', [
            'test' => true,
            'secret' => '',
            'annotations' => false,
        ]);
        $container->extension('dbp_relay_mono', [
            'database_url' => 'sqlite:///:memory:',
        ]);
    }
}
