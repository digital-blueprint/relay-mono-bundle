<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Cron;

use Dbp\Relay\CoreBundle\Cron\CronJobInterface;
use Dbp\Relay\CoreBundle\Cron\CronOptions;
use Dbp\Relay\MonoBundle\Service\PaymentService;

class ReportingCronJob implements CronJobInterface
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
        return 'Mono payment reporting';
    }

    public function getInterval(): string
    {
        return '0 0 * * *';
    }

    public function run(CronOptions $options): void
    {
        $this->paymentService->sendAllReporting();
    }
}
