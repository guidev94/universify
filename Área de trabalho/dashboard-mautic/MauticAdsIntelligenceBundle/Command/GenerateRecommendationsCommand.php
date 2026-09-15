<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\NegativeKeywordRecommendationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:generate-recommendations',
    description: 'Generates AI-powered optimization recommendations for Ads campaigns'
)]
class GenerateRecommendationsCommand extends Command
{
    public function __construct(
        private readonly NegativeKeywordRecommendationService $negativeKeywordRecommendationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Generating AI recommendations...</info>');

        $result = $this->negativeKeywordRecommendationService->generate();

        $output->writeln(sprintf(
            '<info>Negative keyword recommendations: %d created, %d skipped, %d no-interest reasons analyzed.</info>',
            $result['created'],
            $result['skipped'],
            $result['analyzed_reasons'],
        ));

        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
