<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAdGroup;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsKeyword;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\GoogleAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\MicrosoftAdsProvider;

class AdsCampaignSyncService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GoogleAdsProvider $googleAdsProvider,
        private readonly MicrosoftAdsProvider $microsoftAdsProvider,
        private readonly ProviderStatusService $providerStatusService,
    ) {
    }

    /**
     * @return array{synced: int, adGroups: int, keywords: int, campaigns: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function syncGoogleCampaigns(?int $accountId = null): array
    {
        return $this->syncCampaigns(['google'], $accountId);
    }

    /**
     * @param array<int, string> $providers
     *
     * @return array{synced: int, adGroups: int, keywords: int, campaigns: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function syncCampaigns(array $providers = ['google', 'microsoft'], ?int $accountId = null): array
    {
        $criteria = [];

        if (null !== $accountId) {
            $criteria['id'] = $accountId;
        }

        $accounts = $this->entityManager->getRepository(AdsAccount::class)->findBy($criteria);

        $result = [
            'synced'    => 0,
            'adGroups'  => 0,
            'keywords'  => 0,
            'campaigns' => [],
            'errors'    => [],
        ];

        foreach ($accounts as $account) {
            if (!$account instanceof AdsAccount) {
                continue;
            }

            if (!in_array($account->getProvider(), $providers, true)) {
                continue;
            }

            if (!$this->providerStatusService->isProviderEnabled($account->getProvider())) {
                continue;
            }

            $provider = $this->getProvider($account->getProvider());
            if (null === $provider) {
                $result['errors'][] = sprintf('%s: No provider configured.', $account->getProvider());
                continue;
            }

            try {
                $campaigns = $provider->syncCampaigns($account);
            } catch (\Throwable $e) {
                $result['errors'][] = sprintf('%s: %s', $account->getExternalAccountId(), $e->getMessage());
                continue;
            }

            foreach ($campaigns as $campaignData) {
                $this->upsertCampaign($account, $campaignData);
                ++$result['synced'];
                $result['campaigns'][] = $campaignData;
            }

            $this->entityManager->flush();

            try {
                foreach ($provider->syncAdGroups($account) as $adGroupData) {
                    $this->upsertAdGroup($account, $adGroupData);
                    ++$result['adGroups'];
                }

                $this->entityManager->flush();
            } catch (\Throwable $e) {
                $result['errors'][] = sprintf('%s ad groups: %s', $account->getExternalAccountId(), $e->getMessage());
            }

            try {
                foreach ($provider->syncKeywords($account) as $keywordData) {
                    $this->upsertKeyword($account, $keywordData);
                    ++$result['keywords'];
                }

                $this->entityManager->flush();
            } catch (\Throwable $e) {
                $result['errors'][] = sprintf('%s keywords: %s', $account->getExternalAccountId(), $e->getMessage());
            }
        }

        return $result;
    }

    private function getProvider(string $provider): GoogleAdsProvider|MicrosoftAdsProvider|null
    {
        return match ($provider) {
            'google'    => $this->googleAdsProvider,
            'microsoft' => $this->microsoftAdsProvider,
            default     => null,
        };
    }

    /**
     * @param array<string, mixed> $campaignData
     */
    private function upsertCampaign(AdsAccount $account, array $campaignData): void
    {
        $externalCampaignId = (string) ($campaignData['externalCampaignId'] ?? '');
        if ('' === $externalCampaignId) {
            return;
        }

        $campaign = $this->entityManager->getRepository(AdsCampaign::class)->findOneBy([
            'provider'           => $account->getProvider(),
            'externalCampaignId' => $externalCampaignId,
        ]);

        if (!$campaign instanceof AdsCampaign) {
            $campaign = new AdsCampaign();
            $campaign->setProvider($account->getProvider())
                ->setExternalCampaignId($externalCampaignId)
                ->setCreatedAt(new \DateTime());
        }

        $campaign->setAccountId((int) $account->getId())
            ->setName((string) ($campaignData['name'] ?? 'Google Ads Campaign '.$externalCampaignId))
            ->setObjective($campaignData['objective'] ?? null)
            ->setChannelType($campaignData['channelType'] ?? null)
            ->setStatus($campaignData['status'] ?? null)
            ->setBudgetAmount($campaignData['budgetAmount'] ?? null)
            ->setBudgetType($campaignData['budgetType'] ?? null)
            ->setStartedAt($campaignData['startedAt'] ?? null)
            ->setEndedAt($campaignData['endedAt'] ?? null)
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($campaign);
    }

    /**
     * @param array<string, mixed> $adGroupData
     */
    private function upsertAdGroup(AdsAccount $account, array $adGroupData): void
    {
        $externalAdGroupId = (string) ($adGroupData['externalAdgroupId'] ?? '');
        if ('' === $externalAdGroupId) {
            return;
        }

        $campaign = $this->findCampaign($account, (string) ($adGroupData['externalCampaignId'] ?? ''));
        if (!$campaign instanceof AdsCampaign || null === $campaign->getId()) {
            return;
        }

        $adGroup = $this->entityManager->getRepository(AdsAdGroup::class)->findOneBy([
            'provider'          => $account->getProvider(),
            'externalAdgroupId' => $externalAdGroupId,
        ]);

        if (!$adGroup instanceof AdsAdGroup) {
            $adGroup = new AdsAdGroup();
            $adGroup->setProvider($account->getProvider())
                ->setExternalAdgroupId($externalAdGroupId)
                ->setCreatedAt(new \DateTime());
        }

        $adGroup->setAccountId((int) $account->getId())
            ->setCampaignId((int) $campaign->getId())
            ->setName((string) ($adGroupData['name'] ?? 'Ad group '.$externalAdGroupId))
            ->setStatus($adGroupData['status'] ?? null)
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($adGroup);
    }

    /**
     * @param array<string, mixed> $keywordData
     */
    private function upsertKeyword(AdsAccount $account, array $keywordData): void
    {
        $keywordText = trim((string) ($keywordData['keywordText'] ?? ''));
        if ('' === $keywordText) {
            return;
        }

        $campaign = $this->findCampaign($account, (string) ($keywordData['externalCampaignId'] ?? ''));
        if (!$campaign instanceof AdsCampaign || null === $campaign->getId()) {
            return;
        }

        $adGroup = $this->findAdGroup($account, (string) ($keywordData['externalAdgroupId'] ?? ''));
        $externalKeywordId = (string) ($keywordData['externalKeywordId'] ?? '');
        $negative = (bool) ($keywordData['negative'] ?? false);
        $matchType = isset($keywordData['matchType']) ? (string) $keywordData['matchType'] : null;

        $criteria = '' !== $externalKeywordId ? [
            'provider'          => $account->getProvider(),
            'accountId'         => (int) $account->getId(),
            'campaignId'        => (int) $campaign->getId(),
            'adgroupId'         => $adGroup instanceof AdsAdGroup ? $adGroup->getId() : null,
            'externalKeywordId' => $externalKeywordId,
        ] : [
            'provider'    => $account->getProvider(),
            'accountId'   => (int) $account->getId(),
            'campaignId'  => (int) $campaign->getId(),
            'adgroupId'   => $adGroup instanceof AdsAdGroup ? $adGroup->getId() : null,
            'keywordText' => $keywordText,
            'matchType'   => $matchType,
            'negative'    => $negative,
        ];

        $keyword = $this->entityManager->getRepository(AdsKeyword::class)->findOneBy($criteria);

        if (!$keyword instanceof AdsKeyword) {
            $keyword = new AdsKeyword();
            $keyword->setProvider($account->getProvider())
                ->setAccountId((int) $account->getId())
                ->setExternalKeywordId('' !== $externalKeywordId ? $externalKeywordId : null)
                ->setCreatedAt(new \DateTime());
        }

        $keyword->setCampaignId((int) $campaign->getId())
            ->setAdgroupId($adGroup instanceof AdsAdGroup ? $adGroup->getId() : null)
            ->setKeywordText($keywordText)
            ->setMatchType($matchType)
            ->setNegative($negative)
            ->setStatus($keywordData['status'] ?? null)
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($keyword);
    }

    private function findCampaign(AdsAccount $account, string $externalCampaignId): ?AdsCampaign
    {
        if ('' === $externalCampaignId) {
            return null;
        }

        $campaign = $this->entityManager->getRepository(AdsCampaign::class)->findOneBy([
            'provider'           => $account->getProvider(),
            'externalCampaignId' => $externalCampaignId,
        ]);

        return $campaign instanceof AdsCampaign ? $campaign : null;
    }

    private function findAdGroup(AdsAccount $account, string $externalAdGroupId): ?AdsAdGroup
    {
        if ('' === $externalAdGroupId) {
            return null;
        }

        $adGroup = $this->entityManager->getRepository(AdsAdGroup::class)->findOneBy([
            'provider'          => $account->getProvider(),
            'externalAdgroupId' => $externalAdGroupId,
        ]);

        return $adGroup instanceof AdsAdGroup ? $adGroup : null;
    }
}
