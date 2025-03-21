<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class ApiTest extends ApiTestCase
{
    public function testIndex(): void
    {
        $client = self::createClient();
        $this->assertNotNull($client);
    }
}
