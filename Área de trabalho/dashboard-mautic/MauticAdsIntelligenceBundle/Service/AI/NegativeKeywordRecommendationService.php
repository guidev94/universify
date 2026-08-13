<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsKeyword;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsRecommendation;
use Psr\Log\LoggerInterface;

class NegativeKeywordRecommendationService
{
    private const REASON_FIELD_ALIAS = 'ads_sem_interesse_motivo';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AiAnalyzerService $aiAnalyzer,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{created: int, skipped: int, analyzed_reasons: int}
     */
    public function generate(): array
    {
        $reasonRows = $this->getNoInterestReasonRows();
        if ([] === $reasonRows) {
            return ['created' => 0, 'skipped' => 0, 'analyzed_reasons' => 0];
        }

        $campaignContexts = $this->buildCampaignContexts($reasonRows);
        $existingKeys     = $this->getExistingRecommendationKeys();
        $created          = 0;
        $skipped          = 0;

        foreach ($campaignContexts as $context) {
            $recommendations = $this->getAiRecommendations($context);
            if ([] === $recommendations) {
                $recommendations = $this->getFallbackRecommendations($context);
            }

            foreach ($recommendations as $recommendationData) {
                $keywordText = $this->normalizeKeyword((string) ($recommendationData['payload']['keyword_text'] ?? ''));
                if ('' === $keywordText) {
                    ++$skipped;
                    continue;
                }

                $key = $this->buildRecommendationKey((int) $context['campaign_id'], $keywordText);
                if (isset($existingKeys[$key]) || $this->campaignAlreadyHasNegativeKeyword((int) $context['campaign_id'], $keywordText)) {
                    ++$skipped;
                    continue;
                }

                $this->em->persist($this->createRecommendation($context, $recommendationData, $keywordText));
                $existingKeys[$key] = true;
                ++$created;
            }
        }

        $this->em->flush();

        return [
            'created'          => $created,
            'skipped'          => $skipped,
            'analyzed_reasons' => count($reasonRows),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNoInterestReasonRows(): array
    {
        $leadMetadata = $this->em->getClassMetadata(Lead::class);
        $leadTable    = $leadMetadata->getTableName();
        $connection   = $this->em->getConnection();

        if (!$connection->createSchemaManager()->tablesExist([$leadTable])) {
            return [];
        }

        if (!array_key_exists(self::REASON_FIELD_ALIAS, $connection->createSchemaManager()->listTableColumns($leadTable))) {
            return [];
        }

        $sql = sprintf(
            'SELECT l.id AS lead_id, l.%1$s AS reason, a.provider, a.account_id, a.campaign_id, a.adgroup_id, a.keyword_id, a.utm_term
            FROM %2$s l
            INNER JOIN ads_lead_attribution a ON a.lead_id = l.id
            WHERE l.%1$s IS NOT NULL AND l.%1$s <> %3$s AND a.campaign_id IS NOT NULL
            ORDER BY l.id DESC
            LIMIT 500',
            $connection->quoteIdentifier(self::REASON_FIELD_ALIAS),
            $connection->quoteIdentifier($leadTable),
            $connection->quote('')
        );

        return $connection->fetchAllAssociative($sql);
    }

    /**
     * @param array<int, array<string, mixed>> $reasonRows
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildCampaignContexts(array $reasonRows): array
    {
        $contexts = [];

        foreach ($reasonRows as $row) {
            $campaignId = (int) ($row['campaign_id'] ?? 0);
            $reason     = trim((string) ($row['reason'] ?? ''));

            if (0 === $campaignId || '' === $reason) {
                continue;
            }

            $contexts[$campaignId] ??= [
                'provider'       => $row['provider'] ?? null,
                'account_id'     => isset($row['account_id']) ? (int) $row['account_id'] : null,
                'campaign_id'    => $campaignId,
                'reasons'        => [],
                'utm_terms'      => [],
                'positive_terms' => [],
            ];

            $contexts[$campaignId]['reasons'][] = [
                'lead_id' => (int) $row['lead_id'],
                'reason'  => $reason,
            ];

            $utmTerm = trim((string) ($row['utm_term'] ?? ''));
            if ('' !== $utmTerm) {
                $contexts[$campaignId]['utm_terms'][] = $utmTerm;
            }
        }

        foreach ($contexts as $campaignId => &$context) {
            $context['reasons']        = array_slice($context['reasons'], 0, 30);
            $context['utm_terms']      = array_values(array_unique($context['utm_terms']));
            $context['positive_terms'] = $this->getPositiveKeywords((int) $campaignId);
        }

        return array_values($contexts);
    }

    /**
     * @return string[]
     */
    private function getPositiveKeywords(int $campaignId): array
    {
        $keywords = $this->em->getRepository(AdsKeyword::class)->findBy([
            'campaignId' => $campaignId,
            'negative'   => false,
        ]);

        $terms = [];
        foreach ($keywords as $keyword) {
            if ($keyword instanceof AdsKeyword && '' !== trim($keyword->getKeywordText())) {
                $terms[] = $keyword->getKeywordText();
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAiRecommendations(array $context): array
    {
        try {
            $result = $this->aiAnalyzer->analyze(
                ['no_interest_reasons' => $context['reasons']],
                ['negative_keyword_context' => $context],
                [],
                [
                    'allowed_action_types' => ['negative_keyword'],
                    'instruction'          => 'Recomende apenas palavras-chave negativas claramente relacionadas aos motivos de leads sem interesse.',
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: negative keyword AI recommendation failed: '.$e->getMessage());

            return [];
        }

        return array_values(array_filter($result['recommendations'], function (array $recommendation): bool {
            return 'negative_keyword' === ($recommendation['type'] ?? null)
                && isset($recommendation['payload']['keyword_text']);
        }));
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>
     */
    private function getFallbackRecommendations(array $context): array
    {
        $candidateCounts = [];
        $sourceTerms     = array_merge($context['positive_terms'], $context['utm_terms']);

        foreach ($sourceTerms as $term) {
            $normalizedTerm = $this->normalizeKeyword((string) $term);
            if ('' === $normalizedTerm) {
                continue;
            }

            foreach ($context['reasons'] as $reasonData) {
                $reason = $this->normalizeText((string) $reasonData['reason']);
                if (str_contains($reason, $normalizedTerm)) {
                    $candidateCounts[$normalizedTerm] = ($candidateCounts[$normalizedTerm] ?? 0) + 1;
                }
            }
        }

        arsort($candidateCounts);

        $recommendations = [];
        foreach (array_slice($candidateCounts, 0, 5, true) as $term => $count) {
            $recommendations[] = [
                'type'       => 'negative_keyword',
                'provider'   => $context['provider'],
                'title'      => sprintf('Negativar "%s"', $term),
                'reason'     => sprintf('Termo apareceu em %d motivo(s) de leads marcados como sem interesse.', $count),
                'risk'       => 'low',
                'confidence' => min(0.95, 0.55 + ($count * 0.1)),
                'payload'    => [
                    'keyword_text' => $term,
                    'match_type'   => 'phrase',
                    'source'       => 'no_interest_reason_fallback',
                    'reason_count' => $count,
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * @return array<string, bool>
     */
    private function getExistingRecommendationKeys(): array
    {
        $recommendations = $this->em->getRepository(AdsRecommendation::class)->findBy([
            'recommendationType' => 'negative_keyword',
            'status'             => 'pending',
        ]);

        $keys = [];
        foreach ($recommendations as $recommendation) {
            if (!$recommendation instanceof AdsRecommendation) {
                continue;
            }

            $payload     = $recommendation->getPayload() ?? [];
            $keywordText = $this->normalizeKeyword((string) ($payload['keyword_text'] ?? ''));
            if (null !== $recommendation->getCampaignId() && '' !== $keywordText) {
                $keys[$this->buildRecommendationKey($recommendation->getCampaignId(), $keywordText)] = true;
            }
        }

        return $keys;
    }

    private function campaignAlreadyHasNegativeKeyword(int $campaignId, string $keywordText): bool
    {
        $keywords = $this->em->getRepository(AdsKeyword::class)->findBy([
            'campaignId' => $campaignId,
            'negative'   => true,
        ]);

        foreach ($keywords as $keyword) {
            if ($keyword instanceof AdsKeyword && $this->normalizeKeyword($keyword->getKeywordText()) === $keywordText) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $recommendationData
     */
    private function createRecommendation(array $context, array $recommendationData, string $keywordText): AdsRecommendation
    {
        $payload = array_merge((array) ($recommendationData['payload'] ?? []), [
            'keyword_text' => $keywordText,
            'match_type'   => $recommendationData['payload']['match_type'] ?? 'phrase',
            'source'       => $recommendationData['payload']['source'] ?? 'no_interest_reasons',
        ]);

        return (new AdsRecommendation())
            ->setProvider($context['provider'])
            ->setAccountId($context['account_id'])
            ->setCampaignId((int) $context['campaign_id'])
            ->setRecommendationType('negative_keyword')
            ->setTitle((string) ($recommendationData['title'] ?? sprintf('Negativar "%s"', $keywordText)))
            ->setDescription((string) ($recommendationData['reason'] ?? 'Motivos de leads sem interesse indicam baixa aderencia para este termo.'))
            ->setReason((string) ($recommendationData['reason'] ?? 'Relacionado aos motivos registrados em leads sem interesse.'))
            ->setExpectedImpact('Reduzir leads sem fit e economizar verba em buscas pouco qualificadas.')
            ->setRiskLevel((string) ($recommendationData['risk'] ?? 'low'))
            ->setConfidenceScore(isset($recommendationData['confidence']) ? (float) $recommendationData['confidence'] : 0.65)
            ->setPriorityScore(isset($recommendationData['priority']) ? (float) $recommendationData['priority'] : 50.0)
            ->setPayload($payload)
            ->setStatus('pending');
    }

    private function buildRecommendationKey(int $campaignId, string $keywordText): string
    {
        return $campaignId.':'.$keywordText;
    }

    private function normalizeKeyword(string $keyword): string
    {
        $keyword = $this->normalizeText($keyword);
        $keyword = preg_replace('/[^a-z0-9\s\-]/', ' ', $keyword) ?? $keyword;
        $keyword = preg_replace('/\s+/', ' ', $keyword) ?? $keyword;

        return trim($keyword);
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return trim($text);
    }
}
