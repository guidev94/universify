<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:sync-metrics',
    description: 'Synchronizes daily metrics from all connected Ads accounts'
)]
class SyncMetricsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Start date (e.g. "-7 days" or "2026-01-01")', '-7 days')
            ->addOption('to', null, InputOption::VALUE_OPTIONAL, 'End date (e.g. "today" or "2026-01-07")', 'today');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = $input->getOption('from');
        $to   = $input->getOption('to');

        $output->writeln(sprintf('<info>Syncing Ads metrics from %s to %s...</info>', $from, $to));

        // TODO: parse dates and call provider->syncMetrics() for each active account

        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
