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
                    ->arrayNode('payment_types')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('service')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->booleanNode('auth_required')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('return_url_override')
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('return_url_expression')
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('notify_url_expression')
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('psp_return_url_expression')
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('data_protection_declaration_url')
                                ->end()
                                ->scalarNode('recipient')
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
                                ->arrayNode('notify_error')
                                    ->children()
                                        ->scalarNode('dsn')
                                        ->end()
                                        ->scalarNode('from')
                                        ->end()
                                        ->scalarNode('to')
                                        ->end()
                                        ->scalarNode('subject')
                                        ->end()
                                        ->scalarNode('html_template')
                                            ->defaultValue('emails/reporting.html.twig')
                                        ->end()
                                        ->scalarNode('completed_begin')
                                            ->defaultValue('-1 hour')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('reporting')
                                    ->children()
                                        ->scalarNode('dsn')
                                        ->end()
                                        ->scalarNode('from')
                                        ->end()
                                        ->scalarNode('to')
                                        ->end()
                                        ->scalarNode('subject')
                                        ->end()
                                        ->scalarNode('html_template')
                                            ->defaultValue('emails/reporting.html.twig')
                                        ->end()
                                        ->scalarNode('created_begin')
                                            ->defaultValue('-1 day')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('cleanup')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('payment_status')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('timeout_before')
                                    ->isRequired()
                                    ->cannotBeEmpty()
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
