<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence;

class FunnelMetricsService
{
    public function __construct(
        private readonly LeadQualityService $leadQualityService,
        private readonly RevenueAttributionService $revenueAttributionService,
    ) {
    }

    /**
     * Computes full funnel metrics for a given dataset (campaign, channel, etc).
     *
     * @param array{
     *     investment: float,
     *     revenue: float,
     *     leads: int,
     *     cold_leads?: int,
     *     warm_leads?: int,
     *     hot_leads: int,
     *     opportunities: int,
     *     customers: int,
     *     lost_leads?: int
     * } $data
     *
     * @return array<string, mixed>
     */
    public function computeMetrics(array $data): array
    {
        $investment   = (float) ($data['investment'] ?? 0);
        $revenue      = (float) ($data['revenue'] ?? 0);
        $leads        = (int) ($data['leads'] ?? 0);
        $coldLeads    = (int) ($data['cold_leads'] ?? 0);
        $warmLeads    = (int) ($data['warm_leads'] ?? 0);
        $hotLeads     = (int) ($data['hot_leads'] ?? 0);
        $opportunities = (int) ($data['opportunities'] ?? 0);
        $customers    = (int) ($data['customers'] ?? 0);
        $lostLeads    = (int) ($data['lost_leads'] ?? 0);

        $leadCounts = [
            'new'         => $coldLeads,
            'warm'        => $warmLeads,
            'hot'         => $hotLeads,
            'opportunity' => $opportunities,
            'customer'    => $customers,
            'lost'        => $lostLeads,
        ];

        return [
            'investment'          => $investment,
            'revenue'             => $revenue,
            'leads'               => $leads,
            'hot_leads'           => $hotLeads,
            'opportunities'       => $opportunities,
            'customers'           => $customers,
            'lost_leads'          => $lostLeads,
            'lqs'                 => $this->leadQualityService->calculateLQS($leadCounts),
            'lqs_percentage'      => $this->leadQualityService->calculateLQSPercentage($leadCounts),
            'quality_label'       => $this->leadQualityService->getQualityLabel(
                $this->leadQualityService->calculateLQSPercentage($leadCounts)
            ),
            'cac'                 => $this->revenueAttributionService->calculateCAC($investment, $customers),
            'roas'                => $this->revenueAttributionService->calculateROAS($revenue, $investment),
            'roi'                 => $this->revenueAttributionService->calculateROI($revenue, $investment),
            'hot_lead_rate'       => $this->revenueAttributionService->calculateHotLeadRate($hotLeads, $leads),
            'customer_rate'       => $this->revenueAttributionService->calculateCustomerRate($customers, $leads),
            'cost_per_hot_lead'   => $this->revenueAttributionService->calculateCostPerHotLead($investment, $hotLeads),
            'cost_per_opportunity' => $this->revenueAttributionService->calculateCostPerOpportunity($investment, $opportunities),
            'average_ticket'      => $this->revenueAttributionService->calculateAverageTicket($revenue, $customers),
        ];
    }
}
