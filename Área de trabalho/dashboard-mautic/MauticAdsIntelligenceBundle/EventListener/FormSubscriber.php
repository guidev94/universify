<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\EventListener;

use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution\LeadAttributionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LeadAttributionService $leadAttributionService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FORM_ON_SUBMIT => ['onFormSubmit', 0],
        ];
    }

    public function onFormSubmit(SubmissionEvent $event): void
    {
        $submission = $event->getSubmission();
        $lead       = $submission->getLead();

        if ($lead === null || $lead->getId() === null) {
            return;
        }

        $request = $event->getRequest();

        if ($request === null) {
            return;
        }

        try {
            $this->leadAttributionService->captureFromRequest($lead->getId(), $request);
        } catch (\Throwable $e) {
            $this->logger->error(
                'AdsIntelligence: failed to capture attribution on form submit for lead '.$lead->getId().': '.$e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
