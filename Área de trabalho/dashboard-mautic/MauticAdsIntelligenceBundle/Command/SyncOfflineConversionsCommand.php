<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:sync-offline-conversions',
    description: 'Sends queued offline conversion events to Ads platforms'
)]
class SyncOfflineConversionsCommand extends Command
{
    public function __construct(
        private readonly OfflineConversionSyncService $syncService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Syncing offline conversions...</info>');

        $result = $this->syncService->sync();

        $output->writeln(sprintf('Processed: %d', $result['processed']));
        $output->writeln(sprintf('Sent: %d', $result['sent']));
        $output->writeln(sprintf('Failed: %d', $result['failed']));

        $output->writeln('<info>Done.</info>');

        return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
