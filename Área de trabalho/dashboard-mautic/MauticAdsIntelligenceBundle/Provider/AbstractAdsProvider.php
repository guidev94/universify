<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Provider;

use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAction;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;

abstract class AbstractAdsProvider implements AdsProviderInterface
{
    public function supportsOfflineConversions(): bool
    {
        return false;
    }

    public function supportsCampaignMutations(): bool
    {
        return false;
    }

    public function supportsBudgetMutations(): bool
    {
        return false;
    }

    /**
     * Decrypts an encrypted token stored in the credential.
     * Placeholder: real implementation should use Mautic's encryption helper.
     */
    protected function decryptToken(?string $encrypted): ?string
    {
        if ($encrypted === null) {
            return null;
        }

        // TODO: integrate with mautic.helper.encryption
        return $encrypted;
    }

    /**
     * Returns a base array structure for provider results.
     *
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    protected function successResult(string $message = 'OK', array $data = []): array
    {
        return ['success' => true, 'message' => $message, 'data' => $data];
    }

    /**
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    protected function errorResult(string $message, array $data = []): array
    {
        return ['success' => false, 'message' => $message, 'data' => $data];
    }

    public function uploadOfflineConversions(AdsAccount $account, array $conversions): array
    {
        return [
            'success'  => false,
            'uploaded' => 0,
            'failed'   => count($conversions),
            'errors'   => ['Provider does not support offline conversions'],
        ];
    }

    public function applyAction(AdsAction $action): array
    {
        return $this->errorResult('Provider does not support mutations');
    }

    /**
     * Default: no auction-insights support. Google/Bing override this.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncAuctionInsights(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return [];
    }
}
