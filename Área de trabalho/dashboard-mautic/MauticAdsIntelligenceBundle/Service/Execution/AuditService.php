<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsActionLog;

class AuditService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Creates an audit log entry.
     *
     * @param array<string, mixed>|null $payload
     */
    public function log(
        ?int $actionId,
        ?string $provider,
        ?int $userId,
        string $eventType,
        string $message,
        ?array $payload = null,
    ): void {
        $log = new AdsActionLog();
        $log->setActionId($actionId);
        $log->setProvider($provider);
        $log->setUserId($userId);
        $log->setEventType($eventType);
        $log->setMessage($message);
        $log->setPayload($payload);

        try {
            $this->em->persist($log);
            $this->em->flush();
        } catch (\Throwable) {
            // Audit logging must never break the main flow
        }
    }
}
