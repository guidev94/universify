<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsAccountSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:sync-accounts',
    description: 'Synchronizes Ads provider accounts into Mautic'
)]
class SyncAccountsCommand extends Command
{
    public function __construct(
        private readonly AdsAccountSyncService $adsAccountSyncService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Syncing Ads accounts...</info>');

        $result = $this->adsAccountSyncService->syncAccounts();

        foreach ($result['accounts'] as $accountData) {
            $output->writeln(sprintf(
                '  - %s (%s)',
                (string) ($accountData['name'] ?? 'Google Ads account'),
                (string) ($accountData['externalAccountId'] ?? 'unknown')
            ));
        }

        foreach ($result['errors'] as $error) {
            $output->writeln('<error>'.$error.'</error>');
        }

        $output->writeln(sprintf('<info>Done. %d account(s) synced.</info>', $result['synced']));

        return [] === $result['errors'] ? Command::SUCCESS : Command::FAILURE;
    }
}
