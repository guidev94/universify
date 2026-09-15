<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\GoogleAdsProvider;

class AdsCampaignCreateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GoogleAdsProvider $googleAdsProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function createGooglePausedCampaign(int $accountId, string $name, float $dailyBudget): array
    {
        $account = $this->entityManager->getRepository(AdsAccount::class)->find($accountId);
        if (!$account instanceof AdsAccount) {
            throw new \InvalidArgumentException('Google Ads account not found.');
        }

        if ('google' !== $account->getProvider()) {
            throw new \InvalidArgumentException('Selected account is not a Google Ads account.');
        }

        $campaignData = $this->googleAdsProvider->createPausedCampaign($account, [
            'name'        => $name,
            'dailyBudget' => $dailyBudget,
        ]);

        $campaign = new AdsCampaign();
        $campaign->setProvider('google')
            ->setAccountId((int) $account->getId())
            ->setExternalCampaignId((string) $campaignData['externalCampaignId'])
            ->setName((string) $campaignData['name'])
            ->setObjective(null)
            ->setChannelType((string) $campaignData['channelType'])
            ->setStatus((string) $campaignData['status'])
            ->setBudgetAmount((float) $campaignData['budgetAmount'])
            ->setBudgetType((string) $campaignData['budgetType'])
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $campaignData;
    }
}
