<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsCampaignSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:sync-campaigns',
    description: 'Synchronizes campaigns from all connected Ads accounts'
)]
class SyncCampaignsCommand extends Command
{
    public function __construct(private readonly AdsCampaignSyncService $adsCampaignSyncService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Syncing Ads campaigns...</info>');

        $result = $this->adsCampaignSyncService->syncCampaigns();

        foreach ($result['campaigns'] as $campaignData) {
            $output->writeln(sprintf(
                '  - %s (%s)',
                (string) ($campaignData['name'] ?? 'Google Ads campaign'),
                (string) ($campaignData['externalCampaignId'] ?? 'unknown')
            ));
        }

        foreach ($result['errors'] as $error) {
            $output->writeln('<error>'.$error.'</error>');
        }

        $output->writeln(sprintf('<info>Done. %d campaign(s) synced.</info>', $result['synced']));

        return [] === $result['errors'] ? Command::SUCCESS : Command::FAILURE;
    }
}
