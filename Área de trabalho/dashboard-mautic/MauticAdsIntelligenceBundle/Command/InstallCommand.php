<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\MigrationRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:install',
    description: 'Installs or upgrades the Mautic Ads Intelligence database schema'
)]
class InstallCommand extends Command
{
    public function __construct(private MigrationRunner $migrationRunner)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running Mautic Ads Intelligence migrations...</info>');

        $this->migrationRunner->run();

        $output->writeln('<info>Mautic Ads Intelligence schema is ready.</info>');

        return Command::SUCCESS;
    }
}
