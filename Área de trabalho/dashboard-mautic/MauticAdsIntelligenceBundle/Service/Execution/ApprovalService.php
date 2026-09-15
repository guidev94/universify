<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAction;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsRecommendation;
use MauticPlugin\MauticAdsIntelligenceBundle\Repository\AdsActionRepository;
use MauticPlugin\MauticAdsIntelligenceBundle\Repository\AdsRecommendationRepository;

class ApprovalService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Approves a recommendation and marks related actions as approved.
     */
    public function approveRecommendation(int $recommendationId, int $userId): bool
    {
        /** @var AdsRecommendationRepository $repo */
        $repo = $this->em->getRepository(AdsRecommendation::class);

        $recommendation = $repo->find($recommendationId);

        if ($recommendation === null || $recommendation->getStatus() !== 'pending') {
            return false;
        }

        $recommendation->setStatus('approved');
        $recommendation->setApprovedAt(new \DateTime());
        $recommendation->setApprovedBy($userId);

        $this->em->flush();

        $this->auditService->log(
            null,
            $recommendation->getProvider(),
            $userId,
            'recommendation_approved',
            sprintf('Recommendation #%d approved: %s', $recommendationId, $recommendation->getTitle())
        );

        return true;
    }

    /**
     * Rejects a recommendation.
     */
    public function rejectRecommendation(int $recommendationId, int $userId): bool
    {
        /** @var AdsRecommendationRepository $repo */
        $repo = $this->em->getRepository(AdsRecommendation::class);

        $recommendation = $repo->find($recommendationId);

        if ($recommendation === null || $recommendation->getStatus() !== 'pending') {
            return false;
        }

        $recommendation->setStatus('rejected');
        $recommendation->setRejectedAt(new \DateTime());
        $recommendation->setRejectedBy($userId);

        $this->em->flush();

        $this->auditService->log(
            null,
            $recommendation->getProvider(),
            $userId,
            'recommendation_rejected',
            sprintf('Recommendation #%d rejected: %s', $recommendationId, $recommendation->getTitle())
        );

        return true;
    }

    /**
     * Approves a single action.
     */
    public function approveAction(int $actionId, int $userId): bool
    {
        /** @var AdsActionRepository $repo */
        $repo   = $this->em->getRepository(AdsAction::class);
        $action = $repo->find($actionId);

        if ($action === null || $action->getStatus() !== 'pending') {
            return false;
        }

        $action->setStatus('approved');
        $action->setApprovedBy($userId);
        $action->setApprovedAt(new \DateTime());

        $this->em->flush();

        $this->auditService->log(
            $actionId,
            $action->getProvider(),
            $userId,
            'action_approved',
            sprintf('Action #%d approved: %s', $actionId, $action->getActionType())
        );

        return true;
    }

    /**
     * Rejects a single action.
     */
    public function rejectAction(int $actionId, int $userId): bool
    {
        /** @var AdsActionRepository $repo */
        $repo   = $this->em->getRepository(AdsAction::class);
        $action = $repo->find($actionId);

        if ($action === null || $action->getStatus() !== 'pending') {
            return false;
        }

        $action->setStatus('rejected');

        $this->em->flush();

        $this->auditService->log(
            $actionId,
            $action->getProvider(),
            $userId,
            'action_rejected',
            sprintf('Action #%d rejected: %s', $actionId, $action->getActionType())
        );

        return true;
    }
}
