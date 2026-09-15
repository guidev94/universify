<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\EventListener;

use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution\LeadAttributionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LeadAttributionService $leadAttributionService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_POST_SAVE => ['onLeadPostSave', 0],
        ];
    }

    public function onLeadPostSave(LeadEvent $event): void
    {
        if (!$event->isNew()) {
            return;
        }

        $lead = $event->getLead();

        if ($lead->getId() === null) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        try {
            $this->leadAttributionService->captureFromRequest($lead->getId(), $request);
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdsIntelligence: failed to capture attribution on lead '.$lead->getId().': '.$e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
