<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Cron;

use Dbp\Relay\CoreBundle\Cron\CronJobInterface;
use Dbp\Relay\CoreBundle\Cron\CronOptions;
use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Reporting\ReportingService;

class NotifyErrorCronJob implements CronJobInterface
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var ReportingService
     */
    private $reportingService;

    public function __construct(
        ConfigurationService $configurationService,
        ReportingService $reportingService
    ) {
        $this->configurationService = $configurationService;
        $this->reportingService = $reportingService;
    }

    public function getName(): string
    {
        return 'Mono payment notify error reporting';
    }

    public function getInterval(): string
    {
        return '0 * * * *';
    }

    public function run(CronOptions $options): void
    {
        $paymentTypes = $this->configurationService->getPaymentTypes();
        foreach ($paymentTypes as $paymentType) {
            $this->reportingService->sendNotifyError($paymentType);
        }
    }
}
