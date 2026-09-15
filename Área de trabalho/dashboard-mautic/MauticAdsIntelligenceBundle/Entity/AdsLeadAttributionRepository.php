<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsLeadAttribution>
 */
class AdsLeadAttributionRepository extends CommonRepository
{
    public function findByLeadId(int $leadId): ?AdsLeadAttribution
    {
        return $this->findOneBy(['leadId' => $leadId]);
    }

    /**
     * @return AdsLeadAttribution[]
     */
    public function findByCampaignId(int $campaignId): array
    {
        return $this->findBy(['campaignId' => $campaignId]);
    }

    public function findByClickId(string $provider, string $clickId): ?AdsLeadAttribution
    {
        return $this->findOneBy(['provider' => $provider, 'clickId' => $clickId]);
    }
}
