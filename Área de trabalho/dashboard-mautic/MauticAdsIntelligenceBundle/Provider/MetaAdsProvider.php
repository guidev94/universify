<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Provider;

use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;

/**
 * Meta Ads (Facebook Ads) provider stub.
 * Full implementation requires the Facebook Business SDK.
 */
class MetaAdsProvider extends AbstractAdsProvider
{
    public function getName(): string
    {
        return 'meta';
    }

    public function getLabel(): string
    {
        return 'Meta Ads';
    }

    public function supportsOfflineConversions(): bool
    {
        return true;
    }

    public function authenticate(array $credentials): array
    {
        throw new \RuntimeException('MetaAdsProvider::authenticate() not yet implemented');
    }

    public function refreshToken(AdsProviderCredential $credential): array
    {
        throw new \RuntimeException('MetaAdsProvider::refreshToken() not yet implemented');
    }

    public function syncAccounts(AdsProviderCredential $credential): array
    {
        return [];
    }

    public function syncCampaigns(AdsAccount $account): array
    {
        return [];
    }

    public function syncAdGroups(AdsAccount $account): array
    {
        return [];
    }

    public function syncAds(AdsAccount $account): array
    {
        return [];
    }

    public function syncKeywords(AdsAccount $account): array
    {
        return []; // Meta does not use keywords
    }

    public function syncMetrics(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return [];
    }
}
