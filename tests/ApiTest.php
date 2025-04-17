<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiTest extends KernelTestCase
{
    public function testIndex(): void
    {
        $this->assertNotNull($this->getContainer());
    }
}
