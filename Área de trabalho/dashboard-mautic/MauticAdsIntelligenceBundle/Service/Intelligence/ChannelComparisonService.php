<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence;

class ChannelComparisonService
{
    public function __construct(
        private readonly FunnelMetricsService $funnelMetricsService,
    ) {
    }

    /**
     * Computes funnel metrics for each channel in the input array and ranks them.
     *
     * @param array<string, array<string, mixed>> $channelData Keyed by channel/provider name
     *
     * @return array<string, array<string, mixed>>
     */
    public function compareChannels(array $channelData): array
    {
        $result = [];

        foreach ($channelData as $channel => $data) {
            $metrics           = $this->funnelMetricsService->computeMetrics($data);
            $metrics['channel'] = $channel;
            $result[$channel]  = $metrics;
        }

        // Sort by ROAS descending (nulls last)
        uasort($result, static function (array $a, array $b): int {
            $roasA = $a['roas'] ?? -PHP_INT_MAX;
            $roasB = $b['roas'] ?? -PHP_INT_MAX;

            return $roasB <=> $roasA;
        });

        return $result;
    }

    /**
     * Returns the best performing channel by a given metric.
     *
     * @param array<string, array<string, mixed>> $channelData
     */
    public function getBestChannel(array $channelData, string $metric = 'roas'): ?string
    {
        if (empty($channelData)) {
            return null;
        }

        $compared = $this->compareChannels($channelData);
        $first    = array_key_first($compared);

        return $first;
    }

    /**
     * Returns channels that are underperforming (negative ROAS or zero customers).
     *
     * @param array<string, array<string, mixed>> $channelData
     *
     * @return string[]
     */
    public function getUnderperformingChannels(array $channelData): array
    {
        $compared     = $this->compareChannels($channelData);
        $underperform = [];

        foreach ($compared as $channel => $metrics) {
            $roas      = $metrics['roas'] ?? null;
            $customers = $metrics['customers'] ?? 0;

            if ($customers === 0 || ($roas !== null && $roas < 1.0)) {
                $underperform[] = $channel;
            }
        }

        return $underperform;
    }
}
