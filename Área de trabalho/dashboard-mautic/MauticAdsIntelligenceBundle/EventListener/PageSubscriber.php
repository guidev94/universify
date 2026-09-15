<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\EventListener;

use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution\LeadAttributionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LeadAttributionService $leadAttributionService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_ON_HIT => ['onPageHit', 0],
        ];
    }

    public function onPageHit(PageHitEvent $event): void
    {
        $hit    = $event->getHit();
        $lead   = $hit->getLead();
        $request = $event->getRequest();

        if ($lead === null || $lead->getId() === null || $request === null) {
            return;
        }

        try {
            $this->leadAttributionService->captureFromRequest($lead->getId(), $request);
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdsIntelligence: failed to capture attribution on page hit for lead '.$lead->getId().': '.$e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
