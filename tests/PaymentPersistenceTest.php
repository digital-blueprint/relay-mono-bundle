<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Tests;

use Dbp\Relay\MonoBundle\ApiPlatform\Payment;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistenceRepository;
use Dbp\Relay\MonoBundle\Persistence\PaymentStatus;
use Doctrine\Bundle\DoctrineBundle\Registry;
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
        $registry = $container->get('doctrine');
        assert($registry instanceof Registry);
        $em = $registry->getManager('dbp_relay_mono_bundle');
        assert($em instanceof EntityManager);
        $this->em = $em;
        $this->em->clear();
        $metaData = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->updateSchema($metaData);
        $repo = $this->em->getRepository(PaymentPersistence::class);
        assert($repo instanceof PaymentPersistenceRepository);
        $this->repo = $repo;
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
        $payment->setDataProtectionDeclarationUrl('https://data');
        $payment->setDataUpdatedAt(new \DateTimeImmutable());
        $payment->setPaymentStatus(PaymentStatus::PREPARED);

        return $payment;
    }

    public function testFromPayment(): void
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

    public function testFindOneActive(): void
    {
        $payment = $this->getPayment('some-id');
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();
        $this->assertNull($this->repo->findOneActive('some-other-id'));
        $this->assertNotNull($this->repo->findOneActive('some-id'));
    }

    public function testCountConcurrent(): void
    {
        $this->assertSame(0, $this->repo->countConcurrent('type'));

        $payment = $this->getPayment('some-id');
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertSame(1, $this->repo->countConcurrent('type'));
        $this->assertSame(0, $this->repo->countConcurrent('othertype'));
    }

    public function testDateTime(): void
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

    public function testCountAuthConcurrent(): void
    {
        $this->assertSame(0, $this->repo->countAuthConcurrent('type'));
        $this->assertSame(0, $this->repo->countAuthConcurrent('type', 'some-user'));

        $payment = $this->getPayment('some-id');
        $payment->setUserIdentifier('user-id');
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertSame(1, $this->repo->countAuthConcurrent('type'));
        $this->assertSame(0, $this->repo->countAuthConcurrent('type', 'other-id'));
        $this->assertSame(1, $this->repo->countAuthConcurrent('type', 'user-id'));
        $this->assertSame(0, $this->repo->countAuthConcurrent('othertype', 'user-id'));
    }

    public function testCountUnauthConcurrent(): void
    {
        $this->assertSame(0, $this->repo->countUnauthConcurrent('type'));
        $this->assertSame(0, $this->repo->countUnauthConcurrent('type', '127.0.0.1'));

        $payment = $this->getPayment('some-id');
        $payment->setClientIp('127.0.0.1');
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('+10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertSame(1, $this->repo->countUnauthConcurrent('type'));
        $this->assertSame(1, $this->repo->countUnauthConcurrent('type', '127.0.0.1'));
        $this->assertSame(0, $this->repo->countUnauthConcurrent('type', '127.0.0.2'));
        $this->assertSame(0, $this->repo->countUnauthConcurrent('othertype', '127.0.0.1'));
    }

    public function testFindUnnotified(): void
    {
        $this->assertCount(0, $this->repo->findUnnotified());

        $payment = $this->getPayment('some-id');
        $payment->setPaymentStatus(PaymentStatus::COMPLETED);
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertCount(1, $this->repo->findUnnotified());
    }

    public function testFindUnnotifiedByTypeCompletedSince(): void
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

    public function testFindByPaymentStatusTimeoutBefore(): void
    {
        $this->assertCount(0, $this->repo->findByPaymentStatusTimeoutBefore(PaymentStatus::COMPLETED, new \DateTimeImmutable()));

        $payment = $this->getPayment('some-id');
        $payment->setPaymentStatus(PaymentStatus::COMPLETED);
        $payment->setTimeoutAt((new \DateTimeImmutable())->modify('-10 minutes'));
        $this->em->persist($payment);
        $this->em->flush();

        $this->assertCount(1, $this->repo->findByPaymentStatusTimeoutBefore(PaymentStatus::COMPLETED, new \DateTimeImmutable()));
    }

    public function testCountByTypeCreatedSince(): void
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

    public function testPaymentWas(): void
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
