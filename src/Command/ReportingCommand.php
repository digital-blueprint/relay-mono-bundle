<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Command;

use Dbp\Relay\MonoBundle\Service\ConfigurationService;
use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportingCommand extends Command
{
    protected static $defaultName = 'dbp:relay-mono:reporting';

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
        parent::__construct();

        $this->configurationService = $configurationService;
        $this->paymentService = $paymentService;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Reporting command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Send reporting mails...');

        $paymentTypes = $this->configurationService->getPaymentTypes();

        foreach ($paymentTypes as $paymentType) {
            if ($paymentType->getReportingConfig()) {
                $this->paymentService->sendReporting($paymentType);
            }
        }

        return 0;
    }
}
