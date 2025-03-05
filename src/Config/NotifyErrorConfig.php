<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Config;

class NotifyErrorConfig extends EmailConfig
{
    public function getCompletedBegin(): string
    {
        return $this->config['completed_begin'];
    }
}
