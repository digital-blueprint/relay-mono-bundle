<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

class ReportingConfig extends EmailConfig
{
    public function getCreatedBegin(): string
    {
        return $this->config['created_begin'];
    }
}
