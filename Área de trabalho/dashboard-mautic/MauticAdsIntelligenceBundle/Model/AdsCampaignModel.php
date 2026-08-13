<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaignRepository;

/**
 * @extends AbstractCommonModel<AdsCampaign>
 */
class AdsCampaignModel extends AbstractCommonModel
{
    public function getRepository(): AdsCampaignRepository
    {
        /** @var AdsCampaignRepository $repo */
        $repo = $this->em->getRepository(AdsCampaign::class);

        return $repo;
    }

    public function getPermissionBase(): string
    {
        return 'ads:intelligence';
    }

    /**
     * @return AdsCampaign[]
     */
    public function getCampaignsByAccount(int $accountId): array
    {
        return $this->getRepository()->findByAccount($accountId);
    }

    public function saveCampaign(AdsCampaign $campaign): void
    {
        $this->em->persist($campaign);
        $this->em->flush();
    }
}
