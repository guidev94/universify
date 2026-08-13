<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class ContactNoInterestReasonSubscriber implements EventSubscriberInterface
{
    private const REASON_FIELD_ALIAS = 'ads_sem_interesse_motivo';

    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectContactReason', 0],
        ];
    }

    public function injectContactReason(CustomContentEvent $event): void
    {
        if ('tabs.content' !== $event->getContext()) {
            return;
        }

        $vars = $event->getVars();
        $lead = $vars['lead'] ?? null;
        if (!$lead instanceof Lead) {
            return;
        }

        $reason = trim((string) $lead->getFieldValue(self::REASON_FIELD_ALIAS));
        if ('' === $reason) {
            return;
        }

        $event->addContent($this->twig->render(
            '@MauticAdsIntelligence/Contact/no_interest_reason.html.twig',
            [
                'lead'   => $lead,
                'reason' => $reason,
            ]
        ));
    }
}
