<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:execute-approved-actions',
    description: 'Executes all approved Ads actions that are pending execution'
)]
class ExecuteApprovedActionsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Executing approved actions...</info>');

        // TODO: fetch approved AdsActions and call ActionExecutorService::execute() for each

        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
