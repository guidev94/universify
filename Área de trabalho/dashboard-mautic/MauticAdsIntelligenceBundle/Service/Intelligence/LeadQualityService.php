<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence;

class LeadQualityService
{
    /**
     * Weights for each lead stage in the LQS formula.
     */
    private const WEIGHTS = [
        'new'          => 1,
        'warm'         => 2,
        'hot'          => 5,
        'opportunity'  => 8,
        'customer'     => 15,
        'lost'         => -3,
    ];

    /**
     * Calculates the Lead Quality Score for a set of lead counts.
     *
     * Formula:
     * LQS = (new × 1) + (warm × 2) + (hot × 5) + (opportunity × 8) + (customer × 15) - (lost × 3)
     *
     * @param array{
     *     new?: int,
     *     warm?: int,
     *     hot?: int,
     *     opportunity?: int,
     *     customer?: int,
     *     lost?: int
     * } $leadCounts
     */
    public function calculateLQS(array $leadCounts): float
    {
        $new         = (int) ($leadCounts['new'] ?? 0);
        $warm        = (int) ($leadCounts['warm'] ?? 0);
        $hot         = (int) ($leadCounts['hot'] ?? 0);
        $opportunity = (int) ($leadCounts['opportunity'] ?? 0);
        $customer    = (int) ($leadCounts['customer'] ?? 0);
        $lost        = (int) ($leadCounts['lost'] ?? 0);

        return ($new * self::WEIGHTS['new'])
            + ($warm * self::WEIGHTS['warm'])
            + ($hot * self::WEIGHTS['hot'])
            + ($opportunity * self::WEIGHTS['opportunity'])
            + ($customer * self::WEIGHTS['customer'])
            + ($lost * self::WEIGHTS['lost']); // already negative
    }

    /**
     * Calculates the maximum possible LQS if all leads were customers.
     *
     * @param array<string, int> $leadCounts
     */
    public function calculateMaxLQS(array $leadCounts): float
    {
        $total = array_sum($leadCounts);

        return (float) ($total * self::WEIGHTS['customer']);
    }

    /**
     * Returns the LQS as a percentage of the maximum possible score.
     *
     * @param array<string, int> $leadCounts
     */
    public function calculateLQSPercentage(array $leadCounts): float
    {
        $max = $this->calculateMaxLQS($leadCounts);

        if ($max <= 0) {
            return 0.0;
        }

        return round($this->calculateLQS($leadCounts) / $max * 100, 2);
    }

    /**
     * Returns a quality label based on the LQS percentage.
     */
    public function getQualityLabel(float $lqsPercentage): string
    {
        return match (true) {
            $lqsPercentage >= 60 => 'high',
            $lqsPercentage >= 30 => 'medium',
            $lqsPercentage > 0   => 'low',
            default              => 'negative',
        };
    }
}
