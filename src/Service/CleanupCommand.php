<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoBundle\Service;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Starting Mono cleanup...');

        try {
            $this->paymentService->cleanup();
            $io->success('Cleanup completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Cleanup failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
