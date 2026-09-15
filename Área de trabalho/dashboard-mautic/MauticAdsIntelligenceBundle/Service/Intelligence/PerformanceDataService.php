<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\GoogleAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\MicrosoftAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\LinkedInAdsProvider;

class PerformanceDataService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GoogleAdsProvider $googleAdsProvider,
        private readonly MicrosoftAdsProvider $microsoftAdsProvider,
        private readonly LinkedInAdsProvider $linkedInAdsProvider,
    ) {
    }

    public function getClickPerformanceData(array $filters): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $conn = $this->entityManager->getConnection();
        [$where, $params] = $this->buildMetricsWhereSql($filters, $from, $to);
        
        $sql = "SELECT 
                    m.metric_date as raw_date,
                    DATE_FORMAT(m.metric_date, '%d/%m/%Y') as date,
                    COALESCE(c.name, CONCAT('Campaign #', m.campaign_id)) as campaign,
                    SUM(m.clicks) as clicks,
                    SUM(m.impressions) as impressions,
                    SUM(m.conversions) as conversions,
                    SUM(m.cost) as cost
                FROM ads_metrics_daily m
                LEFT JOIN ads_campaigns c ON c.id = m.campaign_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY m.metric_date, m.campaign_id, c.name
                ORDER BY m.metric_date ASC, clicks DESC";

        $results = $conn->fetchAllAssociative($sql, $params);

        $labels = []; $impressionsDataset = []; $clicksDataset = []; $tableData = [];
        
        // Accurate Daily Aggregation for Charts
        $dailyAgg = [];
        foreach ($results as $row) {
            $d = $row['date'];
            if (!isset($dailyAgg[$d])) { $dailyAgg[$d] = ['clicks' => 0, 'impressions' => 0]; }
            $dailyAgg[$d]['clicks'] += (int)$row['clicks'];
            $dailyAgg[$d]['impressions'] += (int)$row['impressions'];
        }

        foreach ($dailyAgg as $date => $metrics) {
            $labels[] = $date;
            $impressionsDataset[] = $metrics['impressions'];
            $clicksDataset[] = $metrics['clicks'];
        }

        $maxClicks = 1;
        foreach ($results as $row) { if ((int)$row['clicks'] > $maxClicks) $maxClicks = (int)$row['clicks']; }

        foreach ($results as $row) {
            $i = (int)$row['impressions'];
            $c = (int)$row['clicks'];
            $ctr = $i > 0 ? ($c / $i) * 100 : 0;

            $tableData[] = [
                'date' => $row['date'],
                'campaign' => $row['campaign'],
                'clicks' => $c,
                'impressions' => $i,
                'ctr' => number_format($ctr, 2) . '%',
                'conversions' => number_format((float)$row['conversions'], 0),
                'cost' => 'R$ ' . number_format((float)$row['cost'], 2, ',', '.'),
                'heatmap_intensity' => ($c / $maxClicks) * 100
            ];
        }

        return [
            'labels' => $labels, 
            'datasets' => [['label' => 'Impressões', 'data' => $impressionsDataset], ['label' => 'Cliques', 'data' => $clicksDataset]], 
            'tableData' => $tableData, 
            'filters' => ['date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]
        ];
    }

    public function getKeywordPerformanceData(array $filters): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $conn = $this->entityManager->getConnection();
        [$where, $params] = $this->buildMetricsWhereSql($filters, $from, $to);
        $where[] = "m.keyword_id IS NOT NULL";

        $sql = "SELECT 
                    k.keyword_text as keyword,
                    k.quality_score as qs,
                    k.match_type as match_type,
                    k.status as status,
                    COALESCE(c.name, 'Unknown') as campaign,
                    SUM(m.clicks) as clicks,
                    SUM(m.impressions) as impressions,
                    SUM(m.cost) as cost
                FROM ads_metrics_daily m
                INNER JOIN ads_keywords k ON k.id = m.keyword_id
                LEFT JOIN ads_campaigns c ON c.id = m.campaign_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY k.id, m.campaign_id, c.name
                ORDER BY clicks DESC";

        $results = $conn->fetchAllAssociative($sql, $params);

        $tableData = []; 
        $qsData = array_fill(1, 11, 0); 
        $qsCount = array_fill(1, 11, 0);
        $matchTypeAgg = ['phrase' => ['clicks' => 0, 'cost' => 0], 'broad'  => ['clicks' => 0, 'cost' => 0], 'exact'  => ['clicks' => 0, 'cost' => 0]];

        $maxClicks = 1;
        foreach ($results as $row) { if ((int)$row['clicks'] > $maxClicks) $maxClicks = (int)$row['clicks']; }

        foreach ($results as $row) {
            $c = (int)$row['clicks'];
            $i = (int)$row['impressions'];
            $cost = (float)$row['cost'];
            $qs = $row['qs'] !== null ? (int)$row['qs'] : null;
            $match = strtolower((string)$row['match_type']);

            if ($qs !== null && $qs >= 1 && $qs <= 10) {
                $qsData[$qs] += $cost;
                $qsCount[$qs] += $c;
            }
            if (isset($matchTypeAgg[$match])) {
                $matchTypeAgg[$match]['clicks'] += $c;
                $matchTypeAgg[$match]['cost'] += $cost;
            }

            $tableData[] = [
                'keyword' => $row['keyword'],
                'qs' => $row['qs'] ?? 'null',
                'match' => ucfirst($match),
                'status' => strtolower((string)$row['status']),
                'state' => 'Active',
                'campaign' => $row['campaign'],
                'clicks' => $c,
                'impressions' => $i,
                'ctr' => ($i > 0 ? number_format(($c / $i) * 100, 2, ',', '.') : '0,00') . '%',
                'avg_cpc' => ($c > 0 ? 'R$ ' . number_format($cost / $c, 2, ',', '.') : 'R$ 0,00'),
                'cost' => 'R$ ' . number_format($cost, 2, ',', '.'),
                'heatmap_intensity' => ($c / $maxClicks) * 100
            ];
        }

        $cpcByQs = [];
        for ($q = 1; $q <= 10; $q++) {
            $cpcByQs[] = $qsCount[$q] > 0 ? $qsData[$q] / $qsCount[$q] : 0;
        }

        return [
            'cpcData' => ['labels' => ['1','2','3','4','5','6','7','8','9','10'], 'data' => $cpcByQs],
            'matchTypeData' => [
                'labels' => ['Phrase', 'Broad', 'Exact'],
                'clicks' => [$matchTypeAgg['phrase']['clicks'], $matchTypeAgg['broad']['clicks'], $matchTypeAgg['exact']['clicks']],
                'cost' => [$matchTypeAgg['phrase']['cost'], $matchTypeAgg['broad']['cost'], $matchTypeAgg['exact']['cost']]
            ],
            'tableData' => $tableData,
            'filters' => ['date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]
        ];
    }

    private function buildMetricsWhereSql(array $filters, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $where = ["m.metric_date BETWEEN :dateFrom AND :dateTo"];
        $params = ['dateFrom' => $from->format('Y-m-d'), 'dateTo' => $to->format('Y-m-d')];

        if (!empty($filters['channel']) && $filters['channel'] !== '') {
            $where[] = "m.provider = :provider"; $params['provider'] = (string) $filters['channel'];
        }
        if (!empty($filters['account_id']) && (int)$filters['account_id'] > 0) {
            $where[] = "m.account_id = :accountId"; $params['accountId'] = (int)$filters['account_id'];
        }
        if (!empty($filters['campaign_id']) && (int)$filters['campaign_id'] > 0) {
            $where[] = "m.campaign_id = :campaignId"; $params['campaignId'] = (int)$filters['campaign_id'];
        }
        if (!empty($filters['ad_group_id']) && (int)$filters['ad_group_id'] > 0) {
            $where[] = "m.adgroup_id = :adgroupId"; $params['adgroupId'] = (int)$filters['ad_group_id'];
        }
        if (!empty($filters['device']) && $filters['device'] !== '') {
            $where[] = "m.device = :device"; $params['device'] = (string)$filters['device'];
        }
        return [$where, $params];
    }

    private function resolveDateRange(array $filters): array
    {
        $to = $this->parseDate($filters['date_to'] ?? null) ?? new \DateTimeImmutable('today');
        $from = $this->parseDate($filters['date_from'] ?? null) ?? $to->modify('-29 days');
        if ($from > $to) { [$from, $to] = [$to, $from]; }
        return [$from, $to];
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) return null;
        try { return new \DateTimeImmutable($value); } catch (\Throwable) { return null; }
    }
}
