<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use PHPUnit\Framework\TestCase;

class PaymentPersistenceTest extends TestCase
{
    public function testPaymentWas()
    {
        $payment = new PaymentPersistence();
        $this->assertFalse($payment->wasNotified());
        $this->assertFalse($payment->wasStarted());
        $this->assertFalse($payment->wasCompleted());
        $payment->setNotifiedAt(new \DateTime('now'));
        $this->assertTrue($payment->wasNotified());
        $this->assertFalse($payment->wasStarted());
        $this->assertFalse($payment->wasCompleted());
        $payment->setStartedAt(new \DateTime('now'));
        $this->assertTrue($payment->wasNotified());
        $this->assertTrue($payment->wasStarted());
        $this->assertFalse($payment->wasCompleted());
        $payment->setCompletedAt(new \DateTime('now'));
        $this->assertTrue($payment->wasNotified());
        $this->assertTrue($payment->wasStarted());
        $this->assertTrue($payment->wasCompleted());
    }
}
