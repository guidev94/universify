<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

class RecommendationParserService
{
    /**
     * Parses the raw JSON response from the AI model.
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException When the response is not valid JSON
     * @throws \InvalidArgumentException When the response does not match the expected schema
     */
    public function parse(string $rawResponse): array
    {
        $data = json_decode($rawResponse, true, 20, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('AI response is not a valid JSON object');
        }

        return $data;
    }

    /**
     * Extracts the recommendations list from the parsed response.
     *
     * @param array<string, mixed> $parsed
     *
     * @return array<int, array<string, mixed>>
     */
    public function extractRecommendations(array $parsed): array
    {
        $recommendations = $parsed['recommendations'] ?? [];

        if (!is_array($recommendations)) {
            return [];
        }

        return array_values(array_filter($recommendations, 'is_array'));
    }

    /**
     * Maps a raw recommendation from the AI to an array suitable for creating an AdsRecommendation entity.
     *
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public function mapToEntityData(array $raw): array
    {
        return [
            'provider'             => $raw['provider'] ?? null,
            'recommendationType'   => $raw['type'] ?? 'unknown',
            'title'                => $raw['title'] ?? 'AI Recommendation',
            'description'          => $raw['reason'] ?? '',
            'reason'               => $raw['reason'] ?? null,
            'expectedImpact'       => isset($raw['expected_impact']) ? json_encode($raw['expected_impact']) : null,
            'riskLevel'            => $raw['risk'] ?? 'medium',
            'confidenceScore'      => isset($raw['confidence']) ? (float) $raw['confidence'] : null,
            'requiresApproval'     => (bool) ($raw['requires_approval'] ?? true),
            'payload'              => $raw['payload'] ?? null,
            'externalTargetType'   => $raw['target_type'] ?? null,
            'externalTargetId'     => $raw['target_id'] ?? null,
        ];
    }
}
