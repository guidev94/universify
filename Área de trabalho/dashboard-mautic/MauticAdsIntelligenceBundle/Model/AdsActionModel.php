<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAction;
use MauticPlugin\MauticAdsIntelligenceBundle\Repository\AdsActionRepository;

/**
 * @extends AbstractCommonModel<AdsAction>
 */
class AdsActionModel extends AbstractCommonModel
{
    public function getRepository(): AdsActionRepository
    {
        /** @var AdsActionRepository $repo */
        $repo = $this->em->getRepository(AdsAction::class);

        return $repo;
    }

    public function getPermissionBase(): string
    {
        return 'ads:intelligence';
    }

    /**
     * @return AdsAction[]
     */
    public function getApprovedActions(): array
    {
        return $this->getRepository()->findApproved();
    }

    /**
     * @return AdsAction[]
     */
    public function getPendingActions(): array
    {
        return $this->getRepository()->findPending();
    }

    public function saveAction(AdsAction $action): void
    {
        $this->em->persist($action);
        $this->em->flush();
    }
}
