<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAdGroup;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign;
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
        $conn = $this->doctrine->getManager()->getConnection();

        $dateFrom = (new \DateTimeImmutable('-29 days'))->format('Y-m-d');
        $dateTo   = (new \DateTimeImmutable('today'))->format('Y-m-d');

        // KPIs for the current 30-day period
        $kpi = $conn->fetchAssociative(
            'SELECT
                COALESCE(SUM(impressions), 0) AS impressions,
                COALESCE(SUM(clicks), 0) AS clicks,
                COALESCE(SUM(cost), 0) AS cost,
                COALESCE(SUM(conversions), 0) AS conversions,
                COALESCE(SUM(conversion_value), 0) AS conversion_value,
                COALESCE(SUM(leads), 0) AS leads,
                COALESCE(SUM(hot_leads), 0) AS hot_leads,
                COALESCE(SUM(opportunities), 0) AS opportunities,
                COALESCE(SUM(customers), 0) AS customers,
                COALESCE(SUM(revenue), 0) AS revenue
            FROM ads_metrics_daily
            WHERE metric_date BETWEEN :from AND :to',
            ['from' => $dateFrom, 'to' => $dateTo]
        ) ?: [];

        // Previous 30-day period for comparison
        $prevFrom = (new \DateTimeImmutable('-59 days'))->format('Y-m-d');
        $prevTo   = (new \DateTimeImmutable('-30 days'))->format('Y-m-d');
        $prevKpi = $conn->fetchAssociative(
            'SELECT
                COALESCE(SUM(cost), 0) AS cost,
                COALESCE(SUM(conversions), 0) AS conversions,
                COALESCE(SUM(conversion_value), 0) AS conversion_value,
                COALESCE(SUM(leads), 0) AS leads,
                COALESCE(SUM(hot_leads), 0) AS hot_leads,
                COALESCE(SUM(clicks), 0) AS clicks,
                COALESCE(SUM(impressions), 0) AS impressions,
                COALESCE(SUM(revenue), 0) AS revenue
            FROM ads_metrics_daily
            WHERE metric_date BETWEEN :from AND :to',
            ['from' => $prevFrom, 'to' => $prevTo]
        ) ?: [];

        // Daily series for the chart
        $dailyData = $conn->fetchAllAssociative(
            'SELECT
                DATE_FORMAT(metric_date, \'%Y-%m-%d\') AS metric_date,
                COALESCE(SUM(impressions), 0) AS impressions,
                COALESCE(SUM(clicks), 0) AS clicks,
                COALESCE(SUM(cost), 0) AS cost,
                COALESCE(SUM(conversions), 0) AS conversions,
                COALESCE(SUM(leads), 0) AS leads
            FROM ads_metrics_daily
            WHERE metric_date BETWEEN :from AND :to
            GROUP BY metric_date
            ORDER BY metric_date ASC',
            ['from' => $dateFrom, 'to' => $dateTo]
        );

        // Provider breakdown
        $byProvider = $conn->fetchAllAssociative(
            'SELECT
                provider,
                COALESCE(SUM(impressions), 0) AS impressions,
                COALESCE(SUM(clicks), 0) AS clicks,
                COALESCE(SUM(cost), 0) AS cost,
                COALESCE(SUM(conversions), 0) AS conversions
            FROM ads_metrics_daily
            WHERE metric_date BETWEEN :from AND :to
            GROUP BY provider
            ORDER BY cost DESC',
            ['from' => $dateFrom, 'to' => $dateTo]
        );

        $cost        = (float) ($kpi['cost'] ?? 0);
        $clicks      = (int) ($kpi['clicks'] ?? 0);
        $impressions = (int) ($kpi['impressions'] ?? 0);
        $conversions = (float) ($kpi['conversions'] ?? 0);
        $convValue   = (float) ($kpi['conversion_value'] ?? 0);
        $revenue     = (float) ($kpi['revenue'] ?? 0);
        $customers   = (int) ($kpi['customers'] ?? 0);

        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute'  => 'mautic_ads_intelligence_overview',
                'dateFrom'      => $dateFrom,
                'dateTo'        => $dateTo,
                'kpi'           => [
                    'investment'    => $cost,
                    'revenue'       => $revenue > 0 ? $revenue : $convValue,
                    'roas'          => $cost > 0 ? ($revenue > 0 ? $revenue : $convValue) / $cost : 0,
                    'cac'           => $customers > 0 ? $cost / $customers : 0,
                    'leads'         => (int) ($kpi['leads'] ?? 0),
                    'hot_leads'     => (int) ($kpi['hot_leads'] ?? 0),
                    'opportunities' => (int) ($kpi['opportunities'] ?? 0),
                    'customers'     => $customers,
                    'clicks'        => $clicks,
                    'impressions'   => $impressions,
                    'conversions'   => $conversions,
                    'ctr'           => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
                    'cpc'           => $clicks > 0 ? $cost / $clicks : 0,
                ],
                'prevKpi'       => [
                    'cost'          => (float) ($prevKpi['cost'] ?? 0),
                    'clicks'        => (int) ($prevKpi['clicks'] ?? 0),
                    'impressions'   => (int) ($prevKpi['impressions'] ?? 0),
                    'conversions'   => (float) ($prevKpi['conversions'] ?? 0),
                    'leads'         => (int) ($prevKpi['leads'] ?? 0),
                ],
                'dailyData'     => $dailyData,
                'byProvider'    => $byProvider,
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
        $filters = $request->query->all();
        $data = $performanceDataService->getClickPerformanceData($filters);

        $accounts = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount::class)->findAll();
        
        $campaignCriteria = [];
        if (!empty($filters['account_id']) && (int)$filters['account_id'] > 0) {
            $campaignCriteria['accountId'] = (int) $filters['account_id'];
        }
        $campaigns = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign::class)->findBy($campaignCriteria);
        
        $adGroups = [];
        if (!empty($filters['campaign_id']) && (int)$filters['campaign_id'] > 0) {
            $adGroups = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAdGroup::class)->findBy([
                'campaignId' => (int) $filters['campaign_id']
            ]);
        }

        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_click_performance',
                'chartData'    => $data,
                'tableData'    => $data['tableData'],
                'accounts'     => $accounts,
                'campaigns'    => $campaigns,
                'adGroups'     => $adGroups,
                'filters'      => array_merge($filters, $data['filters'] ?? []),
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
        $filters = $request->query->all();
        $data = $performanceDataService->getKeywordPerformanceData($filters);

        $accounts = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount::class)->findAll();
        
        $campaignCriteria = [];
        if (!empty($filters['account_id']) && (int)$filters['account_id'] > 0) {
            $campaignCriteria['accountId'] = (int) $filters['account_id'];
        }
        $campaigns = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign::class)->findBy($campaignCriteria);

        $adGroups = [];
        if (!empty($filters['campaign_id']) && (int)$filters['campaign_id'] > 0) {
            $adGroups = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAdGroup::class)->findBy([
                'campaignId' => (int) $filters['campaign_id']
            ]);
        }

        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_keyword_performance',
                'cpcData'      => $data['cpcData'],
                'matchTypeData' => $data['matchTypeData'],
                'tableData'    => $data['tableData'],
                'accounts'     => $accounts,
                'campaigns'    => $campaigns,
                'adGroups'     => $adGroups,
                'filters'      => array_merge($filters, $data['filters'] ?? []),
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/keyword_performance.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_keyword_performance',
                'mauticContent' => 'adsIntelligenceKeywordPerformance',
            ],
        ]);
    }

    public function performanceOverviewAction(Request $request): Response
    {
        $filters = $request->query->all();
        $data    = $this->getPerformanceDataService()->getPerformanceOverviewData($filters);

        return $this->delegateView([
            'viewParameters'  => array_merge($this->getFilterViewParameters($filters), [
                'currentRoute'  => 'mautic_ads_intelligence_performance_overview',
                'kpi'           => $data['kpi'],
                'prevKpi'       => $data['prevKpi'],
                'dailyData'     => $data['dailyData'],
                'prevDailyData' => $data['prevDailyData'],
            ]),
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/performance_overview.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_performance_overview',
                'mauticContent' => 'adsIntelligencePerformanceOverview',
            ],
        ]);
    }

    public function accountPerformanceAction(Request $request): Response
    {
        $filters = $request->query->all();
        $data    = $this->getPerformanceDataService()->getAccountPerformanceData($filters);

        return $this->delegateView([
            'viewParameters'  => array_merge($this->getFilterViewParameters($filters), [
                'currentRoute' => 'mautic_ads_intelligence_account_performance',
                'kpi'          => $data['kpi'],
                'prevKpi'      => $data['prevKpi'],
                'dailyData'    => $data['dailyData'],
                'accountRows'  => $data['accountRows'],
            ]),
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/account_performance.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_account_performance',
                'mauticContent' => 'adsIntelligenceAccountPerformance',
            ],
        ]);
    }

    public function searchQueryPerformanceAction(Request $request): Response
    {
        $filters = $request->query->all();
        $data    = $this->getPerformanceDataService()->getSearchQueryPerformanceData($filters);

        return $this->delegateView([
            'viewParameters'  => array_merge($this->getFilterViewParameters($filters), [
                'currentRoute' => 'mautic_ads_intelligence_search_query_performance',
                'rows'         => $data['rows'],
                'maxClicks'    => $data['maxClicks'],
                'total'        => $data['total'],
                'page'         => $data['page'],
                'perPage'      => $data['perPage'],
                'totalPages'   => $data['totalPages'],
            ]),
            'contentTemplate' => '@MauticAdsIntelligence/Dashboard/search_query_performance.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_search_query_performance',
                'mauticContent' => 'adsIntelligenceSearchQueryPerformance',
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

    public function ajaxCampaignsAction(Request $request): Response
    {
        $accountId = $request->query->get('account_id');
        $criteria = [];
        if ($accountId) {
            $criteria['accountId'] = (int) $accountId;
        }
        $campaigns = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign::class)->findBy($criteria);
        
        $data = array_map(fn($c) => ['id' => $c->getId(), 'name' => $c->getName()], $campaigns);
        return $this->json($data);
    }

    public function ajaxAdGroupsAction(Request $request): Response
    {
        $campaignId = $request->query->get('campaign_id');
        $criteria = [];
        if ($campaignId) {
            $criteria['campaignId'] = (int) $campaignId;
        }
        $adGroups = $this->doctrine->getManager()->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAdGroup::class)->findBy($criteria);
        
        $data = array_map(fn($ag) => ['id' => $ag->getId(), 'name' => $ag->getName()], $adGroups);
        return $this->json($data);
    }

    public function ajaxPerformanceDataAction(Request $request): Response
    {
        /** @var PerformanceDataService $performanceDataService */
        $performanceDataService = $this->container->get('mautic.ads_intelligence.service.performance_data');
        $type = $request->query->get('type', 'click');
        
        if ($type === 'keyword') {
            $data = $performanceDataService->getKeywordPerformanceData($request->query->all());
        } else {
            $data = $performanceDataService->getClickPerformanceData($request->query->all());
        }

        return $this->json($data);
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

    private function getPerformanceDataService(): PerformanceDataService
    {
        return $this->container->get('mautic.ads_intelligence.service.performance_data');
    }

    /**
     * Shared filter dropdown options plus the echoed filter values used by the
     * performance dashboards (account / campaign / ad group / date range).
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getFilterViewParameters(array $filters): array
    {
        $em = $this->doctrine->getManager();

        $accounts = $em->getRepository(AdsAccount::class)->findBy([], ['name' => 'ASC']);

        $campaignCriteria = [];
        if (!empty($filters['account_id'])) {
            $campaignCriteria['accountId'] = (int) $filters['account_id'];
        }
        $campaigns = $em->getRepository(AdsCampaign::class)->findBy($campaignCriteria, ['name' => 'ASC']);

        $adgroupCriteria = [];
        if (!empty($filters['account_id'])) {
            $adgroupCriteria['accountId'] = (int) $filters['account_id'];
        }
        if (!empty($filters['campaign_id'])) {
            $adgroupCriteria['campaignId'] = (int) $filters['campaign_id'];
        }
        $adgroups = $em->getRepository(AdsAdGroup::class)->findBy($adgroupCriteria, ['name' => 'ASC']);

        $dateTo = $this->normalizeDateFilter($filters['date_to'] ?? null)
            ?? (new \DateTimeImmutable('today'))->format('Y-m-d');
        $dateFrom = $this->normalizeDateFilter($filters['date_from'] ?? null)
            ?? (new \DateTimeImmutable($dateTo))->modify('-29 days')->format('Y-m-d');

        return [
            'accounts'   => $accounts,
            'campaigns'  => $campaigns,
            'adgroups'   => $adgroups,
            'accountId'  => isset($filters['account_id']) ? (string) $filters['account_id'] : '',
            'campaignId' => isset($filters['campaign_id']) ? (string) $filters['campaign_id'] : '',
            'adgroupId'  => isset($filters['adgroup_id']) ? (string) $filters['adgroup_id'] : '',
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
        ];
    }

    private function normalizeDateFilter(mixed $value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
