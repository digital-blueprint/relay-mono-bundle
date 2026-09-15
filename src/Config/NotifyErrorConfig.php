<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

class NotifyErrorConfig extends EmailConfig
{
    public function getCadence(): string
    {
        return $this->config['cadence'];
    }

    public function getReportAfter(): string
    {
        return $this->config['report_after'];
    }
}
