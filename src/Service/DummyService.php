<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class DummyService implements LoggerAwareInterface
{
    use LoggerAwareTrait;
}
