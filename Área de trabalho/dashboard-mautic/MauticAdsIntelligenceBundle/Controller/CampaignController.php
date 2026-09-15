<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAdGroup;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsKeyword;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsCampaignCreateService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsCampaignSyncService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsKeywordManagementService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CampaignController extends CommonController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'mautic.ads_intelligence.service.campaign_sync' => AdsCampaignSyncService::class,
            'mautic.ads_intelligence.service.campaign_create' => AdsCampaignCreateService::class,
            'mautic.ads_intelligence.service.keyword_management' => AdsKeywordManagementService::class,
        ]);
    }

    public function indexAction(Request $request, int $page = 1): Response
    {
        $campaigns = $this->doctrine->getManager()->getRepository(AdsCampaign::class)->findBy([], [
            'provider' => 'ASC',
            'name'     => 'ASC',
        ]);
        $accounts = $this->doctrine->getManager()->getRepository(AdsAccount::class)->findBy(['provider' => 'google'], [
            'name' => 'ASC',
        ]);
        $accounts = array_values(array_filter($accounts, static function (AdsAccount $account): bool {
            return !(null === $account->getCurrency()
                && null === $account->getTimezone()
                && str_starts_with($account->getName(), 'Google Ads '));
        }));
        $campaignKeywords = $this->getCampaignKeywords();
        $campaignAdGroups = $this->getCampaignAdGroups();

        return $this->delegateView([
            'viewParameters'  => [
                'page'             => $page,
                'campaigns'        => $campaigns,
                'accounts'         => $accounts,
                'campaignKeywords' => $campaignKeywords,
                'campaignAdGroups' => $campaignAdGroups,
                'currentRoute'     => 'mautic_ads_intelligence_campaigns',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/campaigns.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_campaigns',
                'mauticContent' => 'adsIntelligenceCampaigns',
            ],
        ]);
    }

    public function syncAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_sync_campaigns', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $result = $this->container->get('mautic.ads_intelligence.service.campaign_sync')->syncCampaigns();

        if ([] !== $result['errors']) {
            $this->addFlash('error', $this->translator->trans('mautic.ads_intelligence.campaigns.sync_partial_error', [
                '%count%' => count($result['errors']),
            ]));
        } else {
            $this->addFlash('notice', $this->translator->trans('mautic.ads_intelligence.campaigns.sync_success', [
                '%count%' => $result['synced'],
            ]));
        }

        return $this->redirectToRoute('mautic_ads_intelligence_campaigns');
    }

    public function createAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_create_campaign', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $accountId = (int) $request->request->get('account_id');
        $name = trim((string) $request->request->get('name'));
        $dailyBudget = (float) str_replace(',', '.', (string) $request->request->get('daily_budget'));

        if ('' === $name || $dailyBudget <= 0) {
            $this->addFlash('error', 'mautic.ads_intelligence.campaigns.create_invalid');

            return $this->redirectToRoute('mautic_ads_intelligence_campaigns');
        }

        try {
            $campaign = $this->container->get('mautic.ads_intelligence.service.campaign_create')->createGooglePausedCampaign($accountId, $name, $dailyBudget);
            $this->addFlash('notice', $this->translator->trans('mautic.ads_intelligence.campaigns.create_success', [
                '%name%' => $campaign['name'],
            ]));
        } catch (\Throwable $e) {
            $this->addFlash('error', $this->translator->trans('mautic.ads_intelligence.campaigns.create_error', [
                '%message%' => $this->summarizeGoogleAdsError($e->getMessage()),
            ]));
        }

        return $this->redirectToRoute('mautic_ads_intelligence_campaigns');
    }

    public function createKeywordAction(Request $request, int $campaignId): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_create_keyword_'.$campaignId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $negative = 'negative' === (string) $request->request->get('keyword_type', 'negative');
        $adGroupId = $request->request->get('adgroup_id') ? (int) $request->request->get('adgroup_id') : null;
        $newAdGroupName = $request->request->get('new_adgroup_name') ? (string) $request->request->get('new_adgroup_name') : null;

        try {
            $keyword = $this->container->get('mautic.ads_intelligence.service.keyword_management')->createKeyword(
                $campaignId,
                (string) $request->request->get('keyword_text'),
                (string) $request->request->get('match_type', 'BROAD'),
                $negative,
                $adGroupId,
                $newAdGroupName
            );

            $this->addFlash('notice', $this->translator->trans('mautic.ads_intelligence.keywords.create_success', [
                '%keyword%' => $keyword->getKeywordText(),
            ]));
        } catch (\Throwable $e) {
            $this->addFlash('error', $this->translator->trans('mautic.ads_intelligence.keywords.create_error', [
                '%message%' => $this->summarizeGoogleAdsError($e->getMessage()),
            ]));
        }

        return $this->redirectToRoute('mautic_ads_intelligence_campaigns');
    }

    public function deleteKeywordAction(Request $request, int $keywordId): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_delete_keyword_'.$keywordId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->container->get('mautic.ads_intelligence.service.keyword_management')->deleteKeyword($keywordId);
            $this->addFlash('notice', $this->translator->trans('mautic.ads_intelligence.keywords.delete_success'));
        } catch (\Throwable $e) {
            $this->addFlash('error', $this->translator->trans('mautic.ads_intelligence.keywords.delete_error', [
                '%message%' => $this->summarizeGoogleAdsError($e->getMessage()),
            ]));
        }

        return $this->redirectToRoute('mautic_ads_intelligence_campaigns');
    }

    private function summarizeGoogleAdsError(string $message): string
    {
        return mb_substr($message, 0, 1000);
    }

    /**
     * @return array<int, array{positive: array<int, AdsKeyword>, negative: array<int, AdsKeyword>}>
     */
    private function getCampaignKeywords(): array
    {
        $keywords = $this->doctrine->getManager()->getRepository(AdsKeyword::class)->findBy([], [
            'keywordText' => 'ASC',
        ]);
        $campaignKeywords = [];

        foreach ($keywords as $keyword) {
            if (!$keyword instanceof AdsKeyword) {
                continue;
            }

            $campaignId = $keyword->getCampaignId();
            $campaignKeywords[$campaignId] ??= [
                'positive' => [],
                'negative' => [],
            ];

            $campaignKeywords[$campaignId][$keyword->isNegative() ? 'negative' : 'positive'][] = $keyword;
        }

        return $campaignKeywords;
    }

    /**
     * @return array<int, array<int, AdsAdGroup>>
     */
    private function getCampaignAdGroups(): array
    {
        $adGroups = $this->doctrine->getManager()->getRepository(AdsAdGroup::class)->findBy([], [
            'name' => 'ASC',
        ]);
        $campaignAdGroups = [];

        foreach ($adGroups as $adGroup) {
            if (!$adGroup instanceof AdsAdGroup) {
                continue;
            }

            $campaignAdGroups[$adGroup->getCampaignId()][] = $adGroup;
        }

        return $campaignAdGroups;
    }
}
