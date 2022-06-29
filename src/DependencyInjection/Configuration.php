<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dbp_relay_mono');

        $treeBuilder
            ->getRootNode()
                ->children()
                    ->scalarNode('database_url')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->defaultValue('%env(resolve:DATABASE_URL)%')
                    ->end()
                    ->integerNode('payment_session_timeout')
                        ->isRequired()
                        ->min(5)
                        ->max(86400)
                        ->defaultValue(1800)
                    ->end()
                    ->integerNode('payment_session_number_of_uses')
                        ->isRequired()
                        ->min(-1)
                        ->defaultValue(3)
                    ->end()
                    ->arrayNode('payment_types')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('service')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->booleanNode('auth_necessary')
                                ->end()
                                ->scalarNode('return_url_expression')
                                ->end()
                                ->scalarNode('notify_url_expression')
                                ->end()
                                ->scalarNode('data_protection_declaration_url')
                                ->end()
                                ->arrayNode('payment_contracts')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->arrayPrototype()
                                        ->children()
                                            ->scalarNode('service')
                                                ->isRequired()
                                                ->cannotBeEmpty()
                                            ->end()
                                            ->scalarNode('conditions')
                                            ->end()
                                            ->arrayNode('payment_methods')
                                                ->isRequired()
                                                ->cannotBeEmpty()
                                                ->arrayPrototype()
                                                    ->children()
                                                        ->scalarNode('identifier')
                                                        ->end()
                                                        ->scalarNode('name')
                                                        ->end()
                                                        ->scalarNode('image')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
