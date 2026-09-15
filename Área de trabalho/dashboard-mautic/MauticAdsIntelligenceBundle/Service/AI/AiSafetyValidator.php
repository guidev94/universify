<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

class AiSafetyValidator
{
    /**
     * Default guardrail limits.
     *
     * @var array<string, mixed>
     */
    private array $guardrails = [
        'max_budget_increase_per_campaign_pct' => 15.0,
        'max_budget_increase_per_account_pct'  => 20.0,
        'max_budget_decrease_per_campaign_pct' => 30.0,
        'max_auto_actions_per_day'             => 5,
        'forbidden_action_types'               => ['delete_campaign', 'delete_adgroup', 'delete_ad', 'delete_keyword'],
        'always_requires_approval'             => ['pause_campaign', 'create_campaign', 'change_bid_strategy', 'redistribute_budget'],
    ];

    /**
     * @param array<string, mixed> $guardrails
     */
    public function setGuardrails(array $guardrails): void
    {
        $this->guardrails = array_merge($this->guardrails, $guardrails);
    }

    /**
     * Validates a single recommendation payload from the AI.
     *
     * @param array<string, mixed> $recommendation
     *
     * @return array{valid: bool, violations: string[]}
     */
    public function validate(array $recommendation): array
    {
        $violations = [];
        $type       = $recommendation['type'] ?? '';

        // Check forbidden actions
        $forbidden = $this->guardrails['forbidden_action_types'] ?? [];
        if (in_array($type, $forbidden, true)) {
            $violations[] = sprintf('Action type "%s" is forbidden by guardrails', $type);
        }

        // Check budget increase limits
        if ($type === 'increase_budget') {
            $payload         = $recommendation['payload'] ?? [];
            $currentBudget   = (float) ($payload['current_budget'] ?? 0);
            $newBudget       = (float) ($payload['new_budget'] ?? 0);

            if ($currentBudget > 0 && $newBudget > $currentBudget) {
                $increasePct = ($newBudget - $currentBudget) / $currentBudget * 100;
                $maxPct      = (float) $this->guardrails['max_budget_increase_per_campaign_pct'];

                if ($increasePct > $maxPct) {
                    $violations[] = sprintf(
                        'Budget increase of %.1f%% exceeds guardrail limit of %.1f%%',
                        $increasePct,
                        $maxPct
                    );
                }
            }
        }

        // Force requires_approval for certain action types
        $alwaysApproval = $this->guardrails['always_requires_approval'] ?? [];
        if (in_array($type, $alwaysApproval, true) && !($recommendation['requires_approval'] ?? true)) {
            $violations[] = sprintf('Action type "%s" always requires human approval', $type);
        }

        return [
            'valid'      => empty($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Validates multiple recommendations and filters out invalid ones.
     *
     * @param array<int, array<string, mixed>> $recommendations
     *
     * @return array{valid: array<int, array<string, mixed>>, rejected: array<int, array{recommendation: array<string, mixed>, violations: string[]}>}
     */
    public function validateAll(array $recommendations): array
    {
        $valid    = [];
        $rejected = [];

        foreach ($recommendations as $rec) {
            $result = $this->validate($rec);

            if ($result['valid']) {
                // Force requires_approval for high-risk items
                $alwaysApproval = $this->guardrails['always_requires_approval'] ?? [];
                if (in_array($rec['type'] ?? '', $alwaysApproval, true)) {
                    $rec['requires_approval'] = true;
                }

                $valid[] = $rec;
            } else {
                $rejected[] = [
                    'recommendation' => $rec,
                    'violations'     => $result['violations'],
                ];
            }
        }

        return ['valid' => $valid, 'rejected' => $rejected];
    }
}
