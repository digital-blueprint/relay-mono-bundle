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
                    ->scalarNode('payment_session_timeout')
                        ->info('Time after which a created or started payment can no longer be continued. In ISO duration format.')
                        ->defaultValue('PT1800S')
                    ->end()
                    ->arrayNode('payment_types')
                        ->info('A list of payment type configurations. A payment type is a combination of a payment client configuration and a payment service provider configuration.')
                        ->defaultValue([])
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('service')
                                    ->info('The payment client service class (FQCN)')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->booleanNode('auth_required')
                                    ->info('If starting the payment process requires the client to be authenticated')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('return_url_override')
                                    ->info('An URL to which to redirect the user to after the process is finished. This overrides any return URL passed by the payment initiator and is not affected by "return_url_expression"')
                                ->end()
                                ->scalarNode('return_url_expression')
                                    ->info('A Symfony expression for validating the return url provided by the initiator. Gets passed an "url" variable. Should return true if the URL is valid.')
                                    ->defaultValue('false')
                                ->end()
                                ->scalarNode('notify_url_expression')
                                    ->info('A Symfony expression for validating the notify url provided by the initiator. Gets passed an "url" variable. Should return true if the URL is valid.')
                                    ->defaultValue('false')
                                ->end()
                                ->scalarNode('psp_return_url_expression')
                                    ->info('A Symfony expression for validating the PSP return url provided by the initiator. Gets passed an "url" variable. Should return true if the URL is valid.')
                                    ->defaultValue('false')
                                ->end()
                                ->scalarNode('data_protection_declaration_url')
                                    ->info('The data protection declaration url that will be set on the created payment.')
                                ->end()
                                ->scalarNode('recipient')
                                    ->info('The name of the payment recipient, if any.')
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
                                                        ->booleanNode('demo_mode')
                                                            ->defaultFalse()
                                                            ->info('If enabled the payment client will not be notified when a payment is completed')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('concurrency_limits')
                                    ->info('Various limits for how many payments can be active at the same time')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('max_concurrent_payments')
                                            ->info('The maximum of globally active payments that are active, i.e. that have not expired and have not been completed.')
                                        ->end()
                                        ->integerNode('max_concurrent_auth_payments')
                                            ->info('Same as "max_concurrent_payments" but only counts payments from authenticated users')
                                        ->end()
                                        ->integerNode('max_concurrent_auth_payments_per_user')
                                            ->info('Same as "max_concurrent_auth_payments" but is a limit per user')
                                        ->end()
                                        ->integerNode('max_concurrent_unauth_payments')
                                            ->info('Same as "max_concurrent_payments" but only counts payments from unauthenticated users')
                                        ->end()
                                        ->integerNode('max_concurrent_unauth_payments_per_ip')
                                            ->info('Same as "max_concurrent_unauth_payments" but is limited per user IP address')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('notify_error')
                                    ->info('Configuration for reports about recently completed payments that have not been notified')
                                    ->children()
                                        ->scalarNode('dsn')
                                            ->info('The mailer transport DSN to use for sending the email')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('from')
                                            ->info('The sender email address for the reporting emails')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('to')
                                            ->info('The recipient email address for the reporting emails')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('subject')
                                            ->info('The subject line for the reporting emails')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('html_template')
                                            ->info('The Twig template path for the HTML version of the reporting email')
                                            ->defaultValue('emails/reporting.html.twig')
                                        ->end()
                                        ->scalarNode('completed_begin')
                                            ->info('The report includes all payments that have been completed in the last "completed_begin" interval (e.g., P1D for 1 day) but have not been notified yet')
                                            ->defaultValue('P1D')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('reporting')
                                    ->info('Configuration for recurring email reporting about which payments happened recently.')
                                    ->children()
                                        ->scalarNode('dsn')
                                            ->info('The mailer transport DSN to use for sending the email')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('from')
                                            ->info('The sender email address for the reporting emails')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('to')
                                            ->info('The recipient email address for the reporting emails')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('subject')
                                            ->info('The subject line for the reporting emails')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('html_template')
                                            ->info('The Twig template path for the HTML version of the reporting email')
                                            ->defaultValue('emails/reporting.html.twig')
                                        ->end()
                                        ->scalarNode('created_begin')
                                            ->info('The report includes all payments that have been created in the last "created_begin" interval (e.g., P1D for 1 day).')
                                            ->defaultValue('P1D')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('cleanup')
                        ->info('Configuration for when a payment is pruned from the database. By default none are pruned.')
                        ->defaultValue([])
                        ->arrayPrototype()
                            ->children()
                                ->enumNode('payment_status')
                                    ->values(['prepared', 'started', 'pending', 'failed', 'completed'])
                                    ->info('Payment status for which the provided "timeout_before" is used')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('timeout_before')
                                    ->info('Time after the payment has expired (see payment_session_timeout) when the payment will be considered for cleanup. In ISO duration format.')
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
