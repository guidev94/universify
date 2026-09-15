<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Provider;

use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;

/**
 * TikTok Ads provider stub.
 * Full implementation requires the TikTok Business API.
 */
class TikTokAdsProvider extends AbstractAdsProvider
{
    public function getName(): string
    {
        return 'tiktok';
    }

    public function getLabel(): string
    {
        return 'TikTok Ads';
    }

    public function authenticate(array $credentials): array
    {
        throw new \RuntimeException('TikTokAdsProvider::authenticate() not yet implemented');
    }

    public function refreshToken(AdsProviderCredential $credential): array
    {
        throw new \RuntimeException('TikTokAdsProvider::refreshToken() not yet implemented');
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
        return []; // TikTok does not use keywords
    }

    public function syncMetrics(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return [];
    }
}
