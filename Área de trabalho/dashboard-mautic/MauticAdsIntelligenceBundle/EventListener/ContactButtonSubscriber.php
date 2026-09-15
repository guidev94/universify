<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly CorePermissions $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectContactViewButton', 0],
        ];
    }

    public function injectContactViewButton(CustomButtonEvent $event): void
    {
        $contact = $event->getItem();

        [$route, $routeParams] = $event->getRoute(true);

        if (!$contact instanceof Lead || 'mautic_contact_action' !== $route || 'view' !== ($routeParams['objectAction'] ?? null)) {
            return;
        }

        if (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
            return;
        }

        $event->addButton(
            [
                'attr'      => [
                    'data-toggle' => 'ajaxmodal',
                    'data-target' => '#MauticSharedModal',
                    'data-header' => $this->translator->trans('mautic.ads_intelligence.no_interest.modal.header'),
                    'href'        => $this->router->generate('mautic_ads_intelligence_contact_no_interest', ['contactId' => $contact->getId()]),
                ],
                'btnText'   => $this->translator->trans('mautic.ads_intelligence.no_interest.button'),
                'iconClass' => 'ri-user-unfollow-line',
                'priority'  => -10,
            ],
            ButtonHelper::LOCATION_PAGE_ACTIONS
        );
    }
}
