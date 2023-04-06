<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Cron;

use Dbp\Relay\CoreBundle\Cron\CronJobInterface;
use Dbp\Relay\CoreBundle\Cron\CronOptions;
use Dbp\Relay\MonoBundle\Service\ReportingService;

class ReportingCronJob implements CronJobInterface
{
    /**
     * @var ReportingService
     */
    private $reportingService;

    public function __construct(
        ReportingService $reportingService
    ) {
        $this->reportingService = $reportingService;
    }

    public function getName(): string
    {
        return 'Mono payment reporting';
    }

    public function getInterval(): string
    {
        return '0 0 * * *';
    }

    public function run(CronOptions $options): void
    {
        $this->reportingService->sendAllReporting();
    }
}
