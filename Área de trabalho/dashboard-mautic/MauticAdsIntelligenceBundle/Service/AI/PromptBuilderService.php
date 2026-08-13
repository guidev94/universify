<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

class PromptBuilderService
{
    private const BASE_PROMPT = <<<'PROMPT'
Você é um analista sênior de mídia paga, CRM e aquisição B2B.

Você está analisando campanhas integradas ao Mautic.

Seu objetivo é aumentar clientes adquiridos, reduzir CAC e melhorar ROAS.

Não otimize apenas por cliques ou leads.
Priorize clientes, oportunidades, leads quentes e receita.

Nunca recomende aumento de orçamento acima do limite configurado.
Nunca pause campanha inteira sem aprovação humana.
Nunca execute alterações diretamente.
Sempre retorne ações estruturadas.

Quando os dados incluírem motivos de leads marcados como sem interesse, recomende palavras-chave negativas apenas quando houver relação clara entre o motivo e o termo de busca/keyword.
Para esse caso, use type="negative_keyword", target_type="campaign" e payload.keyword_text com a palavra ou frase a negativar. Prefira match_type="phrase" quando não houver certeza para exact.

Responda em JSON válido com o schema abaixo.

PROMPT;

    private const RESPONSE_SCHEMA = <<<'JSON'
{
  "summary": "string",
  "growth_opportunity": "string",
  "estimated_impact": {
    "hot_leads": "string",
    "customers": "string",
    "cac": "string"
  },
  "recommendations": [
    {
      "type": "string",
      "provider": "string",
      "target_type": "string",
      "target_id": "string",
      "title": "string",
      "reason": "string",
      "risk": "low|medium|high",
      "confidence": 0.0,
      "requires_approval": true,
      "payload": {}
    }
  ]
}
JSON;

    /**
     * Builds the full prompt for the AI model.
     *
     * @param array<string, mixed> $context
     */
    public function buildPrompt(array $context): string
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return self::BASE_PROMPT
            . "\nSchema de resposta esperado:\n"
            . self::RESPONSE_SCHEMA
            . "\n\nDados para análise:\n"
            . $contextJson;
    }

    /**
     * Builds the analysis context to be included in the prompt.
     *
     * @param array<string, mixed> $channelMetrics
     * @param array<string, mixed> $campaignMetrics
     * @param array<string, mixed> $actionHistory
     * @param array<string, mixed> $guardrails
     *
     * @return array<string, mixed>
     */
    public function buildContext(
        array $channelMetrics,
        array $campaignMetrics,
        array $actionHistory,
        array $guardrails,
    ): array {
        return [
            'analysis_date'    => (new \DateTime())->format('Y-m-d'),
            'channels'         => $channelMetrics,
            'campaigns'        => $campaignMetrics,
            'action_history'   => $actionHistory,
            'guardrails'       => $guardrails,
        ];
    }
}
