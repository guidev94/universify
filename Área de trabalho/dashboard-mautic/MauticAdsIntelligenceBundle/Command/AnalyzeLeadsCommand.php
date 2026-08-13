<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:analyze-leads',
    description: 'Analyzes leads and updates stage snapshots and quality scores per campaign'
)]
class AnalyzeLeadsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Analyzing leads...</info>');

        // TODO: fetch attributed leads and update quality metrics via LeadQualityService

        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
