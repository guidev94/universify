<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsProviderCredential>
 */
class AdsProviderCredentialRepository extends CommonRepository
{
    /**
     * @return AdsProviderCredential[]
     */
    public function findActive(): array
    {
        return $this->findBy(['status' => 'active']);
    }
}
