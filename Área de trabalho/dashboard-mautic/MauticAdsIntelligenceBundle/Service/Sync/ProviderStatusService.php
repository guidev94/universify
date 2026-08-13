<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Entity\Integration;

class ProviderStatusService
{
    private const PROVIDER_INTEGRATIONS = [
        'google'    => 'AdsIntelligence',
        'microsoft' => 'AdsIntelligenceMicrosoft',
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function isProviderEnabled(string $provider): bool
    {
        $integrationName = self::PROVIDER_INTEGRATIONS[$provider] ?? null;

        if (null === $integrationName) {
            return false;
        }

        $integration = $this->entityManager->getRepository(Integration::class)->findOneBy([
            'name' => $integrationName,
        ]);

        return $integration instanceof Integration && $integration->getIsPublished();
    }
}
