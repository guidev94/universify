<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsCreativeConversation>
 */
class AdsCreativeConversationRepository extends CommonRepository
{
    /**
     * @return AdsCreativeConversation[]
     */
    public function findByUser(int $userId): array
    {
        return $this->findBy(
            ['userId' => $userId],
            ['dateModified' => 'DESC', 'dateAdded' => 'DESC']
        );
    }

    public function findOneByIdAndUser(int $id, int $userId): ?AdsCreativeConversation
    {
        return $this->findOneBy(['id' => $id, 'userId' => $userId]);
    }
}
