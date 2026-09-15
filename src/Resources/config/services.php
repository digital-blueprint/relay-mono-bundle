<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Resources\config;

use Dbp\Relay\MonoBundle\BackendServiceProvider\BackendServiceRegistry;
use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Config\ReportingConfig;
use Dbp\Relay\MonoBundle\Cron\CleanupCronJob;
use Dbp\Relay\MonoBundle\Cron\NotifyCronJob;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\PaymentServiceProviderServiceRegistry;
use Dbp\Relay\MonoBundle\Reporting\NotifyErrorCronJob;
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

    $services->set(ReportingCronJob::class.'.hourly', ReportingCronJob::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$name', 'Mono hourly payment reporting')
        ->arg('$interval', '0 * * * *')
        ->arg('$cadence', ReportingConfig::CADENCE_HOURLY);

    $services->set(ReportingCronJob::class.'.daily', ReportingCronJob::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$name', 'Mono daily payment reporting')
        ->arg('$interval', '0 0 * * *')
        ->arg('$cadence', ReportingConfig::CADENCE_DAILY);

    $services->set(ReportingCronJob::class.'.weekly', ReportingCronJob::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$name', 'Mono weekly payment reporting')
        ->arg('$interval', '0 0 * * 1')
        ->arg('$cadence', ReportingConfig::CADENCE_WEEKLY);

    $services->set(NotifyErrorCronJob::class.'.hourly', NotifyErrorCronJob::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$name', 'Mono hourly payment notify error reporting')
        ->arg('$interval', '0 * * * *')
        ->arg('$cadence', ReportingConfig::CADENCE_HOURLY);

    $services->set(NotifyErrorCronJob::class.'.daily', NotifyErrorCronJob::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$name', 'Mono daily payment notify error reporting')
        ->arg('$interval', '0 0 * * *')
        ->arg('$cadence', ReportingConfig::CADENCE_DAILY);

    $services->set(NotifyErrorCronJob::class.'.weekly', NotifyErrorCronJob::class)
        ->autowire()
        ->autoconfigure()
        ->arg('$name', 'Mono weekly payment notify error reporting')
        ->arg('$interval', '0 0 * * 1')
        ->arg('$cadence', ReportingConfig::CADENCE_WEEKLY);

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
