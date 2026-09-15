<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAction;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\AdsProviderInterface;
use Psr\Log\LoggerInterface;

class ActionExecutorService
{
    /** @var array<string, AdsProviderInterface> */
    private array $providers = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GuardrailService $guardrailService,
        private readonly AuditService $auditService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function registerProvider(AdsProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Executes a single approved action.
     *
     * @return array{success: bool, message: string}
     */
    public function execute(int $actionId): array
    {
        $action = $this->em->getRepository(AdsAction::class)->find($actionId);

        if ($action === null) {
            return ['success' => false, 'message' => 'Action not found'];
        }

        if ($action->getStatus() !== 'approved') {
            return ['success' => false, 'message' => 'Action is not in approved state'];
        }

        // Validate guardrails before execution
        $guardrailCheck = $this->guardrailService->validateAction($action);

        if (!$guardrailCheck['valid']) {
            $message = 'Guardrail violation: '.implode(', ', $guardrailCheck['violations']);
            $action->setStatus('failed');
            $action->setFailedAt(new \DateTime());
            $action->setErrorMessage($message);
            $this->em->flush();

            $this->auditService->log(
                $actionId,
                $action->getProvider(),
                null,
                'action_blocked',
                $message
            );

            return ['success' => false, 'message' => $message];
        }

        $provider = $this->providers[$action->getProvider()] ?? null;

        if ($provider === null) {
            $message = sprintf('No provider registered for "%s"', $action->getProvider());
            $action->setStatus('failed');
            $action->setFailedAt(new \DateTime());
            $action->setErrorMessage($message);
            $this->em->flush();

            return ['success' => false, 'message' => $message];
        }

        $action->setStatus('executing');
        $this->em->flush();

        try {
            $result = $provider->applyAction($action);

            if ($result['success']) {
                $action->setStatus('executed');
                $action->setExecutedAt(new \DateTime());

                $this->auditService->log(
                    $actionId,
                    $action->getProvider(),
                    null,
                    'action_executed',
                    sprintf('Action #%d executed successfully: %s', $actionId, $action->getActionType())
                );
            } else {
                $action->setStatus('failed');
                $action->setFailedAt(new \DateTime());
                $action->setErrorMessage($result['message']);

                $this->auditService->log(
                    $actionId,
                    $action->getProvider(),
                    null,
                    'action_failed',
                    sprintf('Action #%d failed: %s', $actionId, $result['message'])
                );
            }

            $this->em->flush();

            return $result;
        } catch (\Throwable $e) {
            $action->setStatus('failed');
            $action->setFailedAt(new \DateTime());
            $action->setErrorMessage($e->getMessage());
            $this->em->flush();

            $this->logger->error('AdsIntelligence: action #'.$actionId.' threw exception: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
