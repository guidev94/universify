<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence\PerformanceDataService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends CommonController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'mautic.ads_intelligence.service.performance_data' => PerformanceDataService::class,
        ]);
    }

    public function overviewAction(Request $request): Response
    {
        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_overview',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/overview.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_overview',
                'mauticContent' => 'adsIntelligenceOverview',
            ],
        ]);
    }

    public function channelsAction(Request $request): Response
    {
        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_channels',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/channels.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_channels',
                'mauticContent' => 'adsIntelligenceChannels',
            ],
        ]);
    }

    public function clickPerformanceAction(Request $request): Response
    {
        /** @var PerformanceDataService $performanceDataService */
        $performanceDataService = $this->container->get('mautic.ads_intelligence.service.performance_data');
        $data = $performanceDataService->getClickPerformanceData($request->query->all());

        $accounts = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount::class)->findAll();
        $campaigns = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign::class)->findAll();

        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_click_performance',
                'chartData'    => $data,
                'tableData'    => $data['tableData'],
                'accounts'     => $accounts,
                'campaigns'    => $campaigns,
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/click_performance.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_click_performance',
                'mauticContent' => 'adsIntelligenceClickPerformance',
            ],
        ]);
    }

    public function keywordPerformanceAction(Request $request): Response
    {
        /** @var PerformanceDataService $performanceDataService */
        $performanceDataService = $this->container->get('mautic.ads_intelligence.service.performance_data');
        $data = $performanceDataService->getKeywordPerformanceData($request->query->all());

        $accounts = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount::class)->findAll();
        $campaigns = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign::class)->findAll();

        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_keyword_performance',
                'cpcData'      => $data['cpcData'],
                'matchTypeData' => $data['matchTypeData'],
                'tableData'    => $data['tableData'],
                'accounts'     => $accounts,
                'campaigns'    => $campaigns,
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/keyword_performance.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_keyword_performance',
                'mauticContent' => 'adsIntelligenceKeywordPerformance',
            ],
        ]);
    }

    public function campaignsAction(Request $request): Response
    {
        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_campaigns',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/campaigns.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_campaigns',
                'mauticContent' => 'adsIntelligenceCampaigns',
            ],
        ]);
    }

    public function attributedLeadsAction(Request $request): Response
    {
        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_attributed_leads',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/attributed_leads.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_attributed_leads',
                'mauticContent' => 'adsIntelligenceAttributedLeads',
            ],
        ]);
    }

    public function logsAction(Request $request): Response
    {
        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_logs',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/logs.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_logs',
                'mauticContent' => 'adsIntelligenceLogs',
            ],
        ]);
    }
}
