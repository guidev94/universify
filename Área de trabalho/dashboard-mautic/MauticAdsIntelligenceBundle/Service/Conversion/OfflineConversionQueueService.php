<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsConversionQueue;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsLeadAttribution;

class OfflineConversionQueueService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OfflineConversionMapper $mapper,
    ) {
    }

    /**
     * Enqueues a conversion event for later sync to the Ads provider.
     */
    public function enqueue(
        int $leadId,
        int $accountId,
        string $provider,
        string $eventType,
        \DateTimeInterface $eventTime,
        ?AdsLeadAttribution $attribution = null,
        ?float $revenueOverride = null,
        ?string $email = null,
        ?string $phone = null,
        string $currency = 'BRL',
    ): AdsConversionQueue {
        $queue = $this->mapper->mapEvent(
            $leadId,
            $accountId,
            $provider,
            $eventType,
            $eventTime,
            $attribution,
            $revenueOverride,
            $currency,
        );

        if ($email !== null || $phone !== null) {
            $this->mapper->applyHashedPii($queue, $email, $phone);
        }

        $this->em->persist($queue);
        $this->em->flush();

        return $queue;
    }

    /**
     * Returns all pending conversions grouped by provider.
     *
     * @return array<string, AdsConversionQueue[]>
     */
    public function getPendingGroupedByProvider(): array
    {
        $pending = $this->em->getRepository(AdsConversionQueue::class)
            ->findBy(['status' => 'pending'], ['createdAt' => 'ASC']);

        $grouped = [];

        foreach ($pending as $item) {
            $grouped[$item->getProvider()][] = $item;
        }

        return $grouped;
    }

    /**
     * Marks a conversion as sent.
     */
    public function markSent(AdsConversionQueue $queue): void
    {
        $queue->setStatus('sent');
        $queue->setSentAt(new \DateTime());
        $this->em->flush();
    }

    /**
     * Marks a conversion as failed and increments the attempt counter.
     */
    public function markFailed(AdsConversionQueue $queue, string $error): void
    {
        $queue->setStatus('failed');
        $queue->setAttempts($queue->getAttempts() + 1);
        $queue->setLastError($error);

        // Re-queue if under max attempts
        if ($queue->getAttempts() < 3) {
            $queue->setStatus('pending');
        }

        $this->em->flush();
    }
}
