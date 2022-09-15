<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponse;
use PHPUnit\Framework\TestCase;

class MiscTest extends TestCase
{
    public function testCompleteResponse()
    {
        $response = new CompleteResponse('http://foo');
        $this->assertSame('http://foo', $response->getReturnUrl());
    }
}
