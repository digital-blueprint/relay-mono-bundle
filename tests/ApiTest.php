<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;

class ApiTest extends ApiTestCase
{
    public function testIndex()
    {
        $client = self::createClient();
        $this->assertNotNull($client);
    }
}
