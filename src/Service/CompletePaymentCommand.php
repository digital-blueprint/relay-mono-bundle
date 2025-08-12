<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is mainly for debugging/testing, to trigger a complete action
 * manually in case there is no webhook call.
 */
class CompletePaymentCommand extends Command
{
    public function __construct(
        private PaymentService $paymentService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('dbp:relay:mono:complete-payment');
        $this
            ->setDescription('Try to complete a payment (in case the webhook has failed)')
            ->addArgument('payment-id', InputArgument::REQUIRED, 'The payment ID of the payment to try to complete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paymentId = $input->getArgument('payment-id');

        $response = $this->paymentService->completePayAction($paymentId);
        $output->writeln('Return URL: '.($response->getReturnUrl() ?? ''));

        return Command::SUCCESS;
    }
}
