<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsCampaign>
 */
class AdsCampaignRepository extends CommonRepository
{
    /**
     * @return AdsCampaign[]
     */
    public function findByAccount(int $accountId): array
    {
        return $this->findBy(['accountId' => $accountId]);
    }

    /**
     * @return AdsCampaign[]
     */
    public function findByProvider(string $provider): array
    {
        return $this->findBy(['provider' => $provider]);
    }
}
