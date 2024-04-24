<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpRelayMonoExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    use ExtensionTrait;

    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $this->addPathToHide($container, '/mono/start-pay-actions/{identifier}');
        $this->addPathToHide($container, '/mono/start-pay-actions');
        $this->addPathToHide($container, '/mono/complete-pay-actions');
        $this->addPathToHide($container, '/mono/complete-pay-actions/{identifier}');
        $this->addPathToHide($container, '/mono/payments');

        // Legacy endpoints
        $this->addPathToHide($container, '/mono/payment');
        $this->addPathToHide($container, '/mono/payment', 'POST');
        $this->addPathToHide($container, '/mono/payment/{identifier}');

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $definition = $container->getDefinition(ConfigurationService::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach (['doctrine', 'doctrine_migrations'] as $extKey) {
            if (!$container->hasExtension($extKey)) {
                throw new \Exception("'".$this->getAlias()."' requires the '$extKey' bundle to be loaded!");
            }
        }

        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'connections' => [
                    'dbp_relay_mono_bundle' => [
                        'url' => $config['database_url'] ?? '',
                    ],
                ],
            ],
            'orm' => [
                'entity_managers' => [
                    'dbp_relay_mono_bundle' => [
                        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                        'connection' => 'dbp_relay_mono_bundle',
                        'mappings' => [
                            'dbp_relay_mono' => [
                                'type' => 'attribute',
                                'dir' => __DIR__.'/../Persistence',
                                'prefix' => 'Dbp\Relay\MonoBundle\Persistence',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->registerEntityManager($container, 'dbp_relay_mono_bundle');

        $container->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => [
                'Dbp\Relay\MonoBundle\Migrations' => __DIR__.'/../Migrations',
            ],
        ]);

        $this->registerLoggingChannel($container, 'dbp_relay_mono_audit', false);
    }
}
