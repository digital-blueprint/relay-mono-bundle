<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Command;

use Dbp\Relay\MonoBundle\Service\PaymentService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportingCommand extends Command
{
    protected static $defaultName = 'dbp:relay-mono:reporting';

    /**
     * @var PaymentService
     */
    private $paymentService;

    public function __construct(
        PaymentService $paymentService
    ) {
        parent::__construct();

        $this->paymentService = $paymentService;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Reporting command')
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Override email address to send report to', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = trim($input->getOption('email'));

        if ($email !== '') {
            $output->writeln('Override email address: '.$email);
        }

        $output->writeln('Send reporting mail...');

        $this->paymentService->sendAllReporting($email);

        return 0;
    }
}
