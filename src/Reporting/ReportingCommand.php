<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Reporting;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportingCommand extends Command
{
    /**
     * @var ReportingService
     */
    private $reportingService;

    public function __construct(
        ReportingService $reportingService
    ) {
        parent::__construct();

        $this->reportingService = $reportingService;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('dbp:relay-mono:reporting');
        $this
            ->setDescription('Reporting command')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Override email address to send report to');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('email');

        if ($email !== null) {
            $output->writeln('Override email address: '.$email);
        }

        $output->writeln('Send reporting mail...');

        $this->reportingService->sendAllReporting($email);

        return 0;
    }
}
