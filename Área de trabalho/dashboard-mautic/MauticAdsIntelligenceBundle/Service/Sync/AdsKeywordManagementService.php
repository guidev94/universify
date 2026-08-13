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

class AdsKeywordManagementService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GoogleAdsProvider $googleAdsProvider,
        private readonly MicrosoftAdsProvider $microsoftAdsProvider,
    ) {
    }

    public function createKeyword(int $campaignId, string $keywordText, string $matchType, bool $negative, ?int $adGroupId = null, ?string $newAdGroupName = null): AdsKeyword
    {
        $campaign = $this->getCampaign($campaignId);
        $account = $this->getAccount($campaign);

        $keywordText = trim($keywordText);
        if ('' === $keywordText) {
            throw new \InvalidArgumentException('Keyword text is required.');
        }

        if ('microsoft' === $campaign->getProvider()) {
            $matchType = 'PHRASE';
        }

        $adGroup = null;
        if (!$negative) {
            if (null === $adGroupId && empty($newAdGroupName)) {
                throw new \InvalidArgumentException('Positive keywords require an ad group.');
            }

            if (null !== $adGroupId) {
                $adGroup = $this->getAdGroup($adGroupId, $campaign);
            } elseif (!empty($newAdGroupName)) {
                $providerInstance = $this->getProvider($campaign->getProvider());
                if (!$providerInstance instanceof MicrosoftAdsProvider && !$providerInstance instanceof GoogleAdsProvider) {
                     throw new \RuntimeException('Ad Group creation is not implemented for this provider.');
                }
                
                if ($providerInstance instanceof MicrosoftAdsProvider) {
                    $resultAdGroup = $providerInstance->createAdGroup($account, $campaign, $newAdGroupName);
                    $adGroup = new AdsAdGroup();
                    $adGroup->setCampaignId($campaign->getId())
                            ->setExternalCampaignId($campaign->getExternalCampaignId())
                            ->setExternalAdgroupId($resultAdGroup['id'])
                            ->setName($resultAdGroup['name'])
                            ->setStatus('active')
                            ->setCreatedAt(new \DateTime())
                            ->setUpdatedAt(new \DateTime());
                    $this->entityManager->persist($adGroup);
                    $this->entityManager->flush();
                } else {
                    throw new \RuntimeException('Ad Group creation is not supported for Google Ads yet.');
                }
            }

            $result = $this->getProvider($campaign->getProvider())->createAdGroupKeyword($account, $campaign, $adGroup, $keywordText, $matchType);
        } else {
            $result = $this->getProvider($campaign->getProvider())->createCampaignNegativeKeyword($account, $campaign, $keywordText, $matchType);
        }

        $keyword = new AdsKeyword();
        $keyword->setProvider($campaign->getProvider())
            ->setAccountId((int) $account->getId())
            ->setCampaignId((int) $campaign->getId())
            ->setAdgroupId($adGroup instanceof AdsAdGroup ? $adGroup->getId() : null)
            ->setExternalKeywordId((string) $result['externalKeywordId'])
            ->setKeywordText($keywordText)
            ->setMatchType((string) $result['matchType'])
            ->setNegative($negative)
            ->setStatus((string) $result['status'])
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($keyword);
        $this->entityManager->flush();

        return $keyword;
    }

    public function deleteKeyword(int $keywordId): void
    {
        $keyword = $this->entityManager->getRepository(AdsKeyword::class)->find($keywordId);
        if (!$keyword instanceof AdsKeyword) {
            throw new \InvalidArgumentException('Keyword not found.');
        }

        $campaign = $this->getCampaign($keyword->getCampaignId());
        $account = $this->getAccount($campaign);
        $adGroup = null !== $keyword->getAdgroupId()
            ? $this->entityManager->getRepository(AdsAdGroup::class)->find($keyword->getAdgroupId())
            : null;

        $this->getProvider($campaign->getProvider())->removeKeyword($account, $campaign, $keyword, $adGroup instanceof AdsAdGroup ? $adGroup : null);

        $this->entityManager->remove($keyword);
        $this->entityManager->flush();
    }

    private function getCampaign(int $campaignId): AdsCampaign
    {
        $campaign = $this->entityManager->getRepository(AdsCampaign::class)->find($campaignId);

        if (!$campaign instanceof AdsCampaign) {
            throw new \InvalidArgumentException('Campaign not found.');
        }

        return $campaign;
    }

    private function getProvider(string $provider): GoogleAdsProvider|MicrosoftAdsProvider
    {
        return match ($provider) {
            'google'    => $this->googleAdsProvider,
            'microsoft' => $this->microsoftAdsProvider,
            default     => throw new \RuntimeException('Keyword management is not implemented for '.$provider.'.'),
        };
    }

    private function getAccount(AdsCampaign $campaign): AdsAccount
    {
        $account = $this->entityManager->getRepository(AdsAccount::class)->find($campaign->getAccountId());

        if (!$account instanceof AdsAccount) {
            throw new \InvalidArgumentException('Ads account not found.');
        }

        return $account;
    }

    private function getAdGroup(int $adGroupId, AdsCampaign $campaign): AdsAdGroup
    {
        $adGroup = $this->entityManager->getRepository(AdsAdGroup::class)->find($adGroupId);

        if (!$adGroup instanceof AdsAdGroup || $adGroup->getCampaignId() !== $campaign->getId()) {
            throw new \InvalidArgumentException('Ad group not found for this campaign.');
        }

        return $adGroup;
    }
}
