<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution;

use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAction;

class GuardrailService
{
    /**
     * Default guardrail configuration.
     *
     * @var array<string, mixed>
     */
    private array $guardrails = [
        'max_budget_increase_per_campaign_pct' => 15.0,
        'max_budget_increase_per_account_pct'  => 20.0,
        'max_budget_decrease_per_campaign_pct' => 30.0,
        'max_auto_actions_per_day'             => 5,
        'forbidden_action_types'               => [
            'delete_campaign',
            'delete_adgroup',
            'delete_ad',
            'delete_keyword',
        ],
        'always_requires_approval' => [
            'pause_campaign',
            'create_campaign',
            'change_bid_strategy',
            'redistribute_budget',
        ],
    ];

    /**
     * @param array<string, mixed> $guardrails
     */
    public function configure(array $guardrails): void
    {
        $this->guardrails = array_merge($this->guardrails, $guardrails);
    }

    /**
     * Validates an action against the current guardrails.
     *
     * @return array{valid: bool, violations: string[]}
     */
    public function validateAction(AdsAction $action): array
    {
        $violations = [];
        $type       = $action->getActionType();

        // Check forbidden action types
        $forbidden = $this->guardrails['forbidden_action_types'] ?? [];
        if (in_array($type, $forbidden, true)) {
            $violations[] = sprintf('Action type "%s" is forbidden by guardrails', $type);
        }

        // Check if action requires approval but was not approved
        $alwaysApproval = $this->guardrails['always_requires_approval'] ?? [];
        if (in_array($type, $alwaysApproval, true) && $action->getApprovedBy() === null) {
            $violations[] = sprintf('Action type "%s" always requires human approval', $type);
        }

        // Check budget mutation limits
        if (in_array($type, ['increase_budget', 'decrease_budget'], true)) {
            $payload       = $action->getPayload() ?? [];
            $currentBudget = (float) ($payload['current_budget'] ?? 0);
            $newBudget     = (float) ($payload['new_budget'] ?? 0);

            if ($currentBudget > 0 && $newBudget !== $currentBudget) {
                $changePct = abs(($newBudget - $currentBudget) / $currentBudget * 100);

                if ($type === 'increase_budget') {
                    $maxPct = (float) ($this->guardrails['max_budget_increase_per_campaign_pct'] ?? 15);
                    if ($changePct > $maxPct) {
                        $violations[] = sprintf(
                            'Budget increase of %.1f%% exceeds guardrail limit of %.1f%%',
                            $changePct,
                            $maxPct
                        );
                    }
                } else {
                    $maxPct = (float) ($this->guardrails['max_budget_decrease_per_campaign_pct'] ?? 30);
                    if ($changePct > $maxPct) {
                        $violations[] = sprintf(
                            'Budget decrease of %.1f%% exceeds guardrail limit of %.1f%%',
                            $changePct,
                            $maxPct
                        );
                    }
                }
            }
        }

        return [
            'valid'      => empty($violations),
            'violations' => $violations,
        ];
    }
}
