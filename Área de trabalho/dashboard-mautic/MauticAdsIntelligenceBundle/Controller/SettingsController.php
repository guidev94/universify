<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;
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
        $em = $this->doctrine->getManager();

        // LinkedIn supports several connected accounts — one credential per authorization.
        $linkedinCredentials = $em->getRepository(AdsProviderCredential::class)->findBy(
            ['provider' => 'linkedin'],
            ['createdAt' => 'DESC']
        );

        $linkedinAccounts = [];
        foreach ($linkedinCredentials as $credential) {
            $linkedinAccounts[$credential->getId()] = $em->getRepository(AdsAccount::class)->findBy([
                'provider'     => 'linkedin',
                'credentialId' => $credential->getId(),
            ]);
        }

        // OpenAI Ads uses one API key, and each key is scoped to a single ad account.
        $openaiCredential = $em->getRepository(AdsProviderCredential::class)->findOneBy(['provider' => 'openai']);
        $openaiAccounts   = [];
        if ($openaiCredential instanceof AdsProviderCredential) {
            $openaiAccounts = $em->getRepository(AdsAccount::class)->findBy([
                'provider'     => 'openai',
                'credentialId' => $openaiCredential->getId(),
            ]);
        }

        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute'          => 'mautic_ads_intelligence_settings',
                'geminiSettings'        => $this->getGeminiSettingsService()->getSettings(),
                'systemPromptMaxLength' => CreativeSystemPromptCatalog::MAX_LENGTH,
                'linkedinCredentials'   => $linkedinCredentials,
                'linkedinAccounts'      => $linkedinAccounts,
                'openaiCredential'      => $openaiCredential,
                'openaiAccounts'        => $openaiAccounts,
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
