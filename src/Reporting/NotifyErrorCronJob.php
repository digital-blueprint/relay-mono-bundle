<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Reporting;

use Dbp\Relay\CoreBundle\Cron\CronJobInterface;
use Dbp\Relay\CoreBundle\Cron\CronOptions;

class NotifyErrorCronJob implements CronJobInterface
{
    public function __construct(
        private ReportingService $reportingService,
        private string $name,
        private string $interval,
        private string $cadence,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function run(CronOptions $options): void
    {
        $this->reportingService->sendAllNotifyErrors($this->cadence);
    }
}
