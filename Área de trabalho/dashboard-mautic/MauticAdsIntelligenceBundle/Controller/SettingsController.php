<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeModelCatalog;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeSystemPromptCatalog;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\GeminiSettingsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingsController extends CommonController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'mautic.ads_intelligence.service.gemini_settings' => GeminiSettingsService::class,
        ]);
    }

    public function indexAction(Request $request): Response
    {
        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute'          => 'mautic_ads_intelligence_settings',
                'geminiSettings'        => $this->getGeminiSettingsService()->getSettings(),
                'systemPromptMaxLength' => CreativeSystemPromptCatalog::MAX_LENGTH,
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Settings/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_settings',
                'mauticContent' => 'adsIntelligenceSettings',
            ],
        ]);
    }

    public function saveAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_settings', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->getGeminiSettingsService()->save(
                $this->getRequestString($request, 'gemini_api_key', null),
                $this->getRequestString($request, 'gemini_model', GeminiSettingsService::DEFAULT_MODEL) ?? GeminiSettingsService::DEFAULT_MODEL,
                [
                    CreativeModelCatalog::MODE_TEXT  => $this->getRequestString($request, 'creative_system_prompt_text', '') ?? '',
                    CreativeModelCatalog::MODE_IMAGE => $this->getRequestString($request, 'creative_system_prompt_image', '') ?? '',
                    CreativeModelCatalog::MODE_VIDEO => $this->getRequestString($request, 'creative_system_prompt_video', '') ?? '',
                ],
            );
        } catch (\InvalidArgumentException) {
            $this->addFlash('error', 'mautic.ads_intelligence.settings.invalid');

            return $this->redirectToRoute('mautic_ads_intelligence_settings');
        }

        $this->addFlash('notice', 'mautic.ads_intelligence.settings.saved');

        return $this->redirectToRoute('mautic_ads_intelligence_settings');
    }

    private function getGeminiSettingsService(): GeminiSettingsService
    {
        return $this->container->get('mautic.ads_intelligence.service.gemini_settings');
    }

    private function getRequestString(Request $request, string $key, ?string $default): ?string
    {
        $value = $request->request->all()[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }
}
