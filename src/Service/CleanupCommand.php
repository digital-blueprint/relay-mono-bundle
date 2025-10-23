<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Dbp\Relay\MonoBundle\Config\ConfigurationService;
use Dbp\Relay\MonoBundle\Persistence\PaymentStatus;
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
    public function __construct(private PaymentService $paymentService, private ConfigurationService $configurationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            "Don't delete anything"
        );
        $this->addOption(
            'force-unknown',
            null,
            InputOption::VALUE_NONE,
            'Force cleanup even if there are unknown backend types and payment providers'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $forceUnknown = $input->getOption('force-unknown');

        $io->info('Starting Mono cleanup...');

        $paymentStatuses = [
            PaymentStatus::PREPARED,
            PaymentStatus::STARTED,
            PaymentStatus::PENDING,
            PaymentStatus::COMPLETED,
            PaymentStatus::FAILED,
        ];

        $rows = [];
        foreach ($paymentStatuses as $paymentStatus) {
            $cleanupTimeout = $this->configurationService->getCleanupTimeout($paymentStatus) ?? '-';
            $rows[] = [$paymentStatus, $cleanupTimeout];
        }

        $io->table(
            ['Payment Status', 'Cleanup Timeout'],
            $rows
        );

        $payments = $this->paymentService->collectPaymentsForCleanup($forceUnknown);

        $count = count($payments);
        $io->warning(sprintf('Found %d payment(s) to be cleaned up.', $count));

        if (!$dryRun && !$io->confirm('Do you want to continue?', true)) {
            $io->note('Cleanup cancelled by user.');

            return Command::SUCCESS;
        }

        $this->paymentService->executeCleanup($payments, $dryRun, $forceUnknown);
        $io->success('Cleanup completed successfully!');

        return Command::SUCCESS;
    }
}
