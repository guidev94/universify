<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence;

class RevenueAttributionService
{
    /**
     * CAC = Investment / Customers
     */
    public function calculateCAC(float $investment, int $customers): ?float
    {
        if ($customers <= 0) {
            return null;
        }

        return round($investment / $customers, 2);
    }

    /**
     * ROAS = Revenue / Investment
     */
    public function calculateROAS(float $revenue, float $investment): ?float
    {
        if ($investment <= 0.0) {
            return null;
        }

        return round($revenue / $investment, 4);
    }

    /**
     * ROI = (Revenue - Investment) / Investment * 100
     */
    public function calculateROI(float $revenue, float $investment): ?float
    {
        if ($investment <= 0.0) {
            return null;
        }

        return round(($revenue - $investment) / $investment * 100, 2);
    }

    /**
     * Hot Lead Rate = Hot Leads / Total Leads
     */
    public function calculateHotLeadRate(int $hotLeads, int $totalLeads): ?float
    {
        if ($totalLeads <= 0) {
            return null;
        }

        return round($hotLeads / $totalLeads * 100, 2);
    }

    /**
     * Customer Rate = Customers / Total Leads
     */
    public function calculateCustomerRate(int $customers, int $totalLeads): ?float
    {
        if ($totalLeads <= 0) {
            return null;
        }

        return round($customers / $totalLeads * 100, 2);
    }

    /**
     * Cost per Hot Lead = Investment / Hot Leads
     */
    public function calculateCostPerHotLead(float $investment, int $hotLeads): ?float
    {
        if ($hotLeads <= 0) {
            return null;
        }

        return round($investment / $hotLeads, 2);
    }

    /**
     * Cost per Opportunity = Investment / Opportunities
     */
    public function calculateCostPerOpportunity(float $investment, int $opportunities): ?float
    {
        if ($opportunities <= 0) {
            return null;
        }

        return round($investment / $opportunities, 2);
    }

    /**
     * Average Ticket = Revenue / Customers
     */
    public function calculateAverageTicket(float $revenue, int $customers): ?float
    {
        if ($customers <= 0) {
            return null;
        }

        return round($revenue / $customers, 2);
    }
}
