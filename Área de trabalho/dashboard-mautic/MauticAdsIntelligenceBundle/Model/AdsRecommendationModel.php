<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsRecommendation;
use MauticPlugin\MauticAdsIntelligenceBundle\Repository\AdsRecommendationRepository;

/**
 * @extends AbstractCommonModel<AdsRecommendation>
 */
class AdsRecommendationModel extends AbstractCommonModel
{
    public function getRepository(): AdsRecommendationRepository
    {
        /** @var AdsRecommendationRepository $repo */
        $repo = $this->em->getRepository(AdsRecommendation::class);

        return $repo;
    }

    public function getPermissionBase(): string
    {
        return 'ads:intelligence';
    }

    /**
     * @return AdsRecommendation[]
     */
    public function getPendingRecommendations(): array
    {
        return $this->getRepository()->findPending();
    }

    public function saveRecommendation(AdsRecommendation $recommendation): void
    {
        $this->em->persist($recommendation);
        $this->em->flush();
    }
}
