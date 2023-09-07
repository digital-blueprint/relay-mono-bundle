<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Cron;

use Dbp\Relay\CoreBundle\Cron\CronJobInterface;
use Dbp\Relay\CoreBundle\Cron\CronOptions;
use Dbp\Relay\MonoBundle\Service\PaymentService;

class NotifyCronJob implements CronJobInterface
{
    /**
     * @var PaymentService
     */
    private $paymentService;

    public function __construct(
        PaymentService $paymentService
    ) {
        $this->paymentService = $paymentService;
    }

    public function getName(): string
    {
        return 'Mono notify backend systems';
    }

    public function getInterval(): string
    {
        return '* * * * *';
    }

    public function run(CronOptions $options): void
    {
        $paymentPersistences = $this->paymentService->getUnnotified();

        foreach ($paymentPersistences as $paymentPersistence) {
            $this->paymentService->notifyIfCompleted($paymentPersistence);
        }
    }
}
