<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsRecommendation>
 */
class AdsRecommendationRepository extends CommonRepository
{
    /**
     * @return AdsRecommendation[]
     */
    public function findPending(): array
    {
        return $this->findBy(['status' => 'pending'], ['priorityScore' => 'DESC']);
    }

    /**
     * @return AdsRecommendation[]
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }
}
