<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsAction>
 */
class AdsActionRepository extends CommonRepository
{
    /**
     * @return AdsAction[]
     */
    public function findApproved(): array
    {
        return $this->findBy(['status' => 'approved'], ['createdAt' => 'ASC']);
    }

    /**
     * @return AdsAction[]
     */
    public function findPending(): array
    {
        return $this->findBy(['status' => 'pending'], ['createdAt' => 'ASC']);
    }

    /**
     * @return AdsAction[]
     */
    public function findByRecommendation(int $recommendationId): array
    {
        return $this->findBy(['recommendationId' => $recommendationId]);
    }
}
