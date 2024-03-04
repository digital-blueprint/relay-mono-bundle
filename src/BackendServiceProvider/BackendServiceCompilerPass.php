<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\BackendServiceProvider;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BackendServiceCompilerPass implements CompilerPassInterface
{
    private const TAG = 'dbp.relay.mono.backend_service';

    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(BackendServiceInterface::class)->addTag(self::TAG);
        $container->addCompilerPass(new BackendServiceCompilerPass());
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(BackendService::class)) {
            return;
        }
        $definition = $container->findDefinition(BackendService::class);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addService', [new Reference($id)]);
        }
    }
}
