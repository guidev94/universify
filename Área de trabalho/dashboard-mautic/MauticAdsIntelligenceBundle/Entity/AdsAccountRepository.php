<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsAccount>
 */
class AdsAccountRepository extends CommonRepository
{
    /**
     * @return AdsAccount[]
     */
    public function findActive(): array
    {
        return $this->findBy(['status' => 'active']);
    }

    /**
     * @return AdsAccount[]
     */
    public function findByProvider(string $provider): array
    {
        return $this->findBy(['provider' => $provider]);
    }
}
