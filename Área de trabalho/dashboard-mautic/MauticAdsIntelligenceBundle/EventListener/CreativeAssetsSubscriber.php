<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CreativeAssetsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    public function injectAssets(CustomAssetsEvent $assetsEvent): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $path = $request->getPathInfo();
        if (!str_contains($path, '/ads-intelligence/creatives')) {
            return;
        }

        $assetsEvent->addStylesheet('plugins/MauticAdsIntelligenceBundle/Assets/css/creative-studio.css');
        $assetsEvent->addScript('plugins/MauticAdsIntelligenceBundle/Assets/js/creative-studio.js');
    }
}
