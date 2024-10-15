<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\ApiPlatform\Payment;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistenceRepository;
use Dbp\Relay\MonoBundle\Persistence\PaymentStatus;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentPersistenceTest extends KernelTestCase
{
    private EntityManager $em;

    private PaymentPersistenceRepository $repo;

    public function setUp(): void
    {
        $container = $this->getContainer();
        $this->em = $container->get('doctrine')->getManager('dbp_relay_mono_bundle');
        $this->em->clear();
        $metaData = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->updateSchema($metaData);
        $this->repo = $this->em->getRepository(PaymentPersistence::class);
    }

    public function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
    }

    private function getPayment(string $id): PaymentPersistence
    {
        $payment = new PaymentPersistence();
        $payment->setIdentifier($id);
        $payment->setType('type');
        $payment->setData('data');
        $payment->setClientIp('127.0.0.1');
        $payment->setReturnUrl('https://return');
        $payment->setNotifyUrl('https://notify');
        $payment->setPspReturnUrl('https://pspReturn');
        $payment->setLocalIdentifier('local-id');
        $payment->setPaymentReference('ref');
        $payment->setAmount('42');
        $payment->setCurrency('EUR');
        $payment->setAlternateName('alt');
        $payment->setHonorificPrefix('nope');
        $payment->setGivenName('max');
        $payment->setFamilyName('family');
        $payment->setCompanyName('company');
        $payment->setHonorificSuffix('honorific');
        $payment->setRecipient('recipient');
        $payment->setPaymentMethod('method');
        $payment->setPaymentContract('contract');
        $payment->setDataProtectionDeclarationUrl('https://data');
        $payment->setDataUpdatedAt(new \DateTimeImmutable());
        $payment->setPaymentStatus(PaymentStatus::PREPARED);

        return $payment;
    }

    public function testFromPayment()
    {
        $payment = new Payment();
        $payment->setIdentifier('id');
        $payment->setType('type');
        $payment->setData('data');
        $payment->setPaymentStatus(PaymentStatus::PREPARED);
        $paymentPersistence = PaymentPersistence::fromPayment($payment);
        $this->assertSame('id', $paymentPersistence->getIdentifier());
        $this->assertSame('type', $paymentPersistence->getType());
        $this->assertSame('data', $paymentPersistence->getData());
        $this->assertSame(PaymentStatus::PREPARED, $paymentPersistence->getPaymentStatus());
    }

    public function testFindOneActive()
    {
        $payment = $this->getPayment('some-id');
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();
        $this->assertNull($this->repo->findOneActive('some-other-id'));
        $this->assertNotNull($this->repo->findOneActive('some-id'));
    }

    public function testCountConcurrent()
    {
        $this->assertSame(0, $this->repo->countConcurrent());

        $payment = $this->getPayment('some-id');
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertSame(1, $this->repo->countConcurrent());
    }

    public function testDateTime()
    {
        $payment = $this->getPayment('some-id');
        $payment->setTimeoutAt(new \DateTime());
        $payment->setCompletedAt(new \DateTime());
        $payment->setCreatedAt(new \DateTime());
        $payment->setStartedAt(new \DateTime());
        $payment->setNotifiedAt(new \DateTime());
        $payment->setDataUpdatedAt(new \DateTime());
        $this->em->persist($payment);
        $this->em->flush();

        $this->repo->countByTypeCreatedSince('some_type', new \DateTime());
        $this->repo->findByPaymentStatusTimeoutBefore(PaymentStatus::COMPLETED, new \DateTime());
        $this->repo->findUnnotifiedByTypeCompletedSince('some_type', new \DateTime());

        $this->assertTrue(true);
    }

    public function testCountAuthConcurrent()
    {
        $this->assertSame(0, $this->repo->countAuthConcurrent());
        $this->assertSame(0, $this->repo->countAuthConcurrent('some-user'));

        $payment = $this->getPayment('some-id');
        $payment->setUserIdentifier('user-id');
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertSame(1, $this->repo->countAuthConcurrent());
        $this->assertSame(0, $this->repo->countAuthConcurrent('other-id'));
        $this->assertSame(1, $this->repo->countAuthConcurrent('user-id'));
    }

    public function testCountUnauthConcurrent()
    {
        $this->assertSame(0, $this->repo->countUnauthConcurrent());
        $this->assertSame(0, $this->repo->countUnauthConcurrent('127.0.0.1'));

        $payment = $this->getPayment('some-id');
        $payment->setClientIp('127.0.0.1');
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertSame(1, $this->repo->countUnauthConcurrent());
        $this->assertSame(1, $this->repo->countUnauthConcurrent('127.0.0.1'));
        $this->assertSame(0, $this->repo->countUnauthConcurrent('127.0.0.2'));
    }

    public function testFindUnnotified()
    {
        $this->assertCount(0, $this->repo->findUnnotified());

        $payment = $this->getPayment('some-id');
        $payment->setPaymentStatus(PaymentStatus::COMPLETED);
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertCount(1, $this->repo->findUnnotified());
    }

    public function testFindUnnotifiedByTypeCompletedSince()
    {
        $this->assertCount(0, $this->repo->findUnnotifiedByTypeCompletedSince('some_type', new \DateTimeImmutable()));

        $time = new \DateTimeImmutable();
        $payment = $this->getPayment('some-id');
        $payment->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment->setCompletedAt($time);
        $payment->setType('some_type');
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertCount(1, $this->repo->findUnnotifiedByTypeCompletedSince('some_type', $time));
    }

    public function testFindByPaymentStatusTimeoutBefore()
    {
        $this->assertCount(0, $this->repo->findByPaymentStatusTimeoutBefore(PaymentStatus::COMPLETED, new \DateTimeImmutable()));

        $payment = $this->getPayment('some-id');
        $payment->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('-10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertCount(1, $this->repo->findByPaymentStatusTimeoutBefore(PaymentStatus::COMPLETED, new \DateTimeImmutable()));
    }

    public function testCountByTypeCreatedSince()
    {
        $this->assertCount(0, $this->repo->countByTypeCreatedSince('some_type', new \DateTimeImmutable()));

        $payment = $this->getPayment('some-id');
        $payment->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment->setCreatedAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $payment->setType('some_type');
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertSame([PaymentStatus::COMPLETED => 1], $this->repo->countByTypeCreatedSince('some_type', new \DateTimeImmutable()));
    }

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
