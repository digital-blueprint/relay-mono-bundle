<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Cron;

use Dbp\Relay\CoreBundle\Cron\CronJobInterface;
use Dbp\Relay\CoreBundle\Cron\CronOptions;
use Dbp\Relay\MonoBundle\Service\ConfigurationService;
use Dbp\Relay\MonoBundle\Service\PaymentService;

class NotifyErrorCronJob implements CronJobInterface
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var PaymentService
     */
    private $paymentService;

    public function __construct(
        ConfigurationService $configurationService,
        PaymentService $paymentService
    ) {
        $this->configurationService = $configurationService;
        $this->paymentService = $paymentService;
    }

    public function getName(): string
    {
        return 'Mono payment notify error reporting';
    }

    public function getInterval(): string
    {
        return '0 * * * *';
    }

    public function run(CronOptions $options): void
    {
        $paymentTypes = $this->configurationService->getPaymentTypes();
        foreach ($paymentTypes as $paymentType) {
            if ($paymentType->getNotifyErrorConfig()) {
                $this->paymentService->sendNotifyError($paymentType);
            }
        }
    }
}
