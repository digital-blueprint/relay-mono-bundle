<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dbp:relay:mono:cleanup',
    description: 'Run the Mono payment cleanup process',
)]
class CleanupCommand extends Command
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            "Don't delete anything"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $io->info('Starting Mono cleanup...');

        $payments = $this->paymentService->collectPaymentsForCleanup();

        $count = count($payments);
        $io->warning(sprintf('Found %d payment(s) to be cleaned up.', $count));

        if (!$dryRun && !$io->confirm('Do you want to continue?', true)) {
            $io->note('Cleanup cancelled by user.');

            return Command::SUCCESS;
        }

        $this->paymentService->executeCleanup($payments, $dryRun);
        $io->success('Cleanup completed successfully!');

        return Command::SUCCESS;
    }
}
