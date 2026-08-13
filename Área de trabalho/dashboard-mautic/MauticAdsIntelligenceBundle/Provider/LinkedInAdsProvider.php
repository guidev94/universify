<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Provider;

use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;

/**
 * LinkedIn Ads provider stub.
 * Full implementation requires the LinkedIn Marketing API.
 */
class LinkedInAdsProvider extends AbstractAdsProvider
{
    public function getName(): string
    {
        return 'linkedin';
    }

    public function getLabel(): string
    {
        return 'LinkedIn Ads';
    }

    public function supportsOfflineConversions(): bool
    {
        return true;
    }

    public function authenticate(array $credentials): array
    {
        throw new \RuntimeException('LinkedInAdsProvider::authenticate() not yet implemented');
    }

    public function refreshToken(AdsProviderCredential $credential): array
    {
        throw new \RuntimeException('LinkedInAdsProvider::refreshToken() not yet implemented');
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
        return []; // LinkedIn does not use keywords
    }

    public function syncMetrics(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return [];
    }
}
