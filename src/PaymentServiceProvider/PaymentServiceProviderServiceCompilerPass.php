<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\PaymentServiceProvider;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentServiceProviderServiceCompilerPass implements CompilerPassInterface
{
    private const TAG = 'dbp.relay.mono.payment_service_provider_service';

    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(PaymentServiceProviderServiceInterface::class)->addTag(self::TAG);
        $container->addCompilerPass(new PaymentServiceProviderServiceCompilerPass());
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(PaymentServiceProviderServiceRegistry::class)) {
            return;
        }
        $definition = $container->findDefinition(PaymentServiceProviderServiceRegistry::class);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addService', [new Reference($id)]);
        }
    }
}
