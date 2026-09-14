<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

class NotifyErrorConfig extends EmailConfig
{
    public function getReportAfter(): string
    {
        return $this->config['report_after'];
    }
}
