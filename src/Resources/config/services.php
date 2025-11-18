<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Resources\config;

use Dbp\Relay\MonoBundle\BackendServiceProvider\BackendServiceRegistry;
use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Cron\CleanupCronJob;
use Dbp\Relay\MonoBundle\Cron\NotifyCronJob;
use Dbp\Relay\MonoBundle\Cron\NotifyErrorCronJob;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\PaymentServiceProviderServiceRegistry;
use Dbp\Relay\MonoBundle\Reporting\ReportingCommand;
use Dbp\Relay\MonoBundle\Reporting\ReportingCronJob;
use Dbp\Relay\MonoBundle\Reporting\ReportingService;
use Dbp\Relay\MonoBundle\Service\CleanupCommand;
use Dbp\Relay\MonoBundle\Service\CompletePaymentCommand;
use Dbp\Relay\MonoBundle\Service\HealthCheck;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set(CleanupCronJob::class)
        ->autowire()
        ->autoconfigure();

    $services->set(NotifyCronJob::class)
        ->autowire()
        ->autoconfigure();

    $services->set(NotifyErrorCronJob::class)
        ->autowire()
        ->autoconfigure();

    $services->set(ReportingCronJob::class)
        ->autowire()
        ->autoconfigure();

    $services->set(ReportingCommand::class)
        ->autowire()
        ->autoconfigure();

    $services->load('Dbp\\Relay\\MonoBundle\\ApiPlatform\\', '../../ApiPlatform')
        ->autowire()
        ->autoconfigure();

    $services->set(BackendServiceRegistry::class)
        ->autowire()
        ->autoconfigure();

    $services->set(ConfigurationService::class)
        ->autowire()
        ->autoconfigure();

    $services->set(PaymentService::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$em', service('doctrine.orm.dbp_relay_mono_bundle_entity_manager'))
        ->arg('$auditLogger', service('monolog.logger.dbp_relay_mono_audit'));

    $services->set(ReportingService::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$em', service('doctrine.orm.dbp_relay_mono_bundle_entity_manager'));

    $services->set(PaymentServiceProviderServiceRegistry::class)
        ->autowire()
        ->autoconfigure();

    $services->set(HealthCheck::class)
        ->autowire()
        ->autoconfigure();

    $services->set(CompletePaymentCommand::class)
        ->autowire()
        ->autoconfigure();

    $services->set(CleanupCommand::class)
        ->autowire()
        ->autoconfigure();
};
