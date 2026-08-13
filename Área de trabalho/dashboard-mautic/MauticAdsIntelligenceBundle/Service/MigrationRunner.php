<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\IntegrationsBundle\Migration\Engine;
use Psr\Log\LoggerInterface;

class MigrationRunner
{
    private const BUNDLE_NAME = 'MauticAdsIntelligenceBundle';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function run(): void
    {
        $engine = new Engine(
            $this->entityManager,
            (string) (defined('MAUTIC_TABLE_PREFIX') ? MAUTIC_TABLE_PREFIX : ''),
            dirname(__DIR__),
            self::BUNDLE_NAME,
        );

        $engine->up();

        $this->logger->info('Mautic Ads Intelligence migrations executed.');
    }
}
