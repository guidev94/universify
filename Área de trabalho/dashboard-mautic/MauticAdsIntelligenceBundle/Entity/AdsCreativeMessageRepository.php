<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsCreativeMessage>
 */
class AdsCreativeMessageRepository extends CommonRepository
{
    /**
     * @return AdsCreativeMessage[]
     */
    public function findByConversation(int $conversationId): array
    {
        return $this->findBy(
            ['conversationId' => $conversationId],
            ['dateAdded' => 'ASC', 'id' => 'ASC']
        );
    }

    public function findLastAssistantWithVideo(int $conversationId): ?AdsCreativeMessage
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversationId = :conversationId')
            ->andWhere('m.role = :role')
            ->andWhere('m.mediaType = :mediaType')
            ->andWhere('m.status = :status')
            ->setParameter('conversationId', $conversationId)
            ->setParameter('role', 'assistant')
            ->setParameter('mediaType', 'video')
            ->setParameter('status', AdsCreativeMessage::STATUS_COMPLETED)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
