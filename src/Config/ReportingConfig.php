<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

class ReportingConfig extends EmailConfig
{
    public const CADENCE_HOURLY = 'hourly';
    public const CADENCE_DAILY = 'daily';
    public const CADENCE_WEEKLY = 'weekly';

    public function getCadence(): string
    {
        return $this->config['cadence'];
    }
}
