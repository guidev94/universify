<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Provider;

use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAction;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;

interface AdsProviderInterface
{
    /**
     * Returns the unique machine name of this provider (e.g. "google", "meta").
     */
    public function getName(): string;

    /**
     * Returns the human-readable label of this provider (e.g. "Google Ads").
     */
    public function getLabel(): string;

    /**
     * Whether this provider supports offline conversion uploads.
     */
    public function supportsOfflineConversions(): bool;

    /**
     * Whether this provider supports campaign mutations (e.g. pause/budget changes).
     */
    public function supportsCampaignMutations(): bool;

    /**
     * Whether this provider supports budget mutations.
     */
    public function supportsBudgetMutations(): bool;

    /**
     * Authenticates with the provider using the given credentials.
     * Returns an array with at least: access_token, refresh_token, expires_at.
     *
     * @param array<string, mixed> $credentials
     *
     * @return array<string, mixed>
     */
    public function authenticate(array $credentials): array;

    /**
     * Refreshes the access token for the given credential.
     * Returns an array with at least: access_token, expires_at.
     *
     * @return array<string, mixed>
     */
    public function refreshToken(AdsProviderCredential $credential): array;

    /**
     * Fetches all ad accounts accessible by this credential.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncAccounts(AdsProviderCredential $credential): array;

    /**
     * Fetches all campaigns for the given account.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncCampaigns(AdsAccount $account): array;

    /**
     * Fetches all ad groups for the given account.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncAdGroups(AdsAccount $account): array;

    /**
     * Fetches all ads for the given account.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncAds(AdsAccount $account): array;

    /**
     * Fetches all keywords for the given account.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncKeywords(AdsAccount $account): array;

    /**
     * Fetches daily metrics for the given account and date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncMetrics(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Uploads offline conversion data to the provider.
     *
     * @param array<int, array<string, mixed>> $conversions
     *
     * @return array{success: bool, uploaded: int, failed: int, errors: array<string>}
     */
    public function uploadOfflineConversions(AdsAccount $account, array $conversions): array;

    /**
     * Applies a single action to the provider.
     *
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    public function applyAction(AdsAction $action): array;
}
