<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiAnalyzerService
{
    public function __construct(
        private readonly PromptBuilderService $promptBuilder,
        private readonly RecommendationParserService $recommendationParser,
        private readonly AiSafetyValidator $safetyValidator,
        private readonly GeminiSettingsService $geminiSettings,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Runs the full AI analysis pipeline.
     *
     * @param array<string, mixed> $channelMetrics
     * @param array<string, mixed> $campaignMetrics
     * @param array<string, mixed> $actionHistory
     * @param array<string, mixed> $guardrails
     *
     * @return array{
     *     summary: string,
     *     growth_opportunity: string,
     *     estimated_impact: array<string, string>,
     *     recommendations: array<int, array<string, mixed>>,
     *     rejected: array<int, array<string, mixed>>
     * }
     */
    public function analyze(
        array $channelMetrics,
        array $campaignMetrics,
        array $actionHistory = [],
        array $guardrails = [],
    ): array {
        $context = $this->promptBuilder->buildContext(
            $channelMetrics,
            $campaignMetrics,
            $actionHistory,
            $guardrails,
        );

        $prompt = $this->promptBuilder->buildPrompt($context);

        $rawResponse = $this->callAiModel($prompt);

        if ($rawResponse === null) {
            $this->logger->error('AdsIntelligence: AI model returned empty response');

            return [
                'summary'            => 'AI analysis failed',
                'growth_opportunity' => '',
                'estimated_impact'   => [],
                'recommendations'    => [],
                'rejected'           => [],
            ];
        }

        try {
            $parsed = $this->recommendationParser->parse($rawResponse);
        } catch (\JsonException|\InvalidArgumentException $e) {
            $this->logger->error('AdsIntelligence: failed to parse AI response: '.$e->getMessage());

            return [
                'summary'            => 'AI response parsing failed',
                'growth_opportunity' => '',
                'estimated_impact'   => [],
                'recommendations'    => [],
                'rejected'           => [],
            ];
        }

        $rawRecs    = $this->recommendationParser->extractRecommendations($parsed);
        $validated  = $this->safetyValidator->validateAll($rawRecs);

        return [
            'summary'            => (string) ($parsed['summary'] ?? ''),
            'growth_opportunity' => (string) ($parsed['growth_opportunity'] ?? ''),
            'estimated_impact'   => (array) ($parsed['estimated_impact'] ?? []),
            'recommendations'    => $validated['valid'],
            'rejected'           => $validated['rejected'],
        ];
    }

    /**
     * Calls Gemini and returns the raw JSON string expected by RecommendationParserService.
     */
    private function callAiModel(string $prompt): ?string
    {
        $settings = $this->geminiSettings->getSettings(true);
        if (!$settings['configured'] || empty($settings['apiKey'])) {
            $this->logger->warning('AdsIntelligence: Gemini API is not configured');

            return null;
        }

        $model = $this->sanitizeModelName($settings['model']);
        $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => [
                    'Content-Type'   => 'application/json',
                    'x-goog-api-key' => (string) $settings['apiKey'],
                ],
                'json' => [
                    'contents' => [[
                        'role'  => 'user',
                        'parts' => [[
                            'text' => $prompt,
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature'      => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: Gemini request failed: '.$e->getMessage());

            return null;
        }

        if (($payload['error']['message'] ?? null) !== null) {
            $this->logger->error('AdsIntelligence: Gemini API error: '.$payload['error']['message']);

            return null;
        }

        $text = $payload['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || '' === trim($text)) {
            $this->logger->error('AdsIntelligence: Gemini returned no text response');

            return null;
        }

        return $this->normalizeJsonResponse($text);
    }

    private function sanitizeModelName(string $model): string
    {
        $model = trim($model);
        if (str_starts_with($model, 'models/')) {
            return substr($model, 7);
        }

        return $model;
    }

    private function normalizeJsonResponse(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        return trim($text);
    }
}
