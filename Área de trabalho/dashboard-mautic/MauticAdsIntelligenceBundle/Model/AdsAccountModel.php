<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Repository\AdsAccountRepository;

/**
 * @extends AbstractCommonModel<AdsAccount>
 */
class AdsAccountModel extends AbstractCommonModel
{
    public function getRepository(): AdsAccountRepository
    {
        /** @var AdsAccountRepository $repo */
        $repo = $this->em->getRepository(AdsAccount::class);

        return $repo;
    }

    public function getPermissionBase(): string
    {
        return 'ads:intelligence';
    }

    /**
     * @return AdsAccount[]
     */
    public function getActiveAccounts(): array
    {
        return $this->getRepository()->findActive();
    }

    public function saveAccount(AdsAccount $account): void
    {
        $this->em->persist($account);
        $this->em->flush();
    }
}
