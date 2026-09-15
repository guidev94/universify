<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\GoogleAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\LinkedInAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\MicrosoftAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\OpenAiAdsProvider;

class AdsAccountSyncService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GoogleAdsProvider $googleAdsProvider,
        private readonly MicrosoftAdsProvider $microsoftAdsProvider,
        private readonly LinkedInAdsProvider $linkedInAdsProvider,
        private readonly OpenAiAdsProvider $openAiAdsProvider,
        private readonly ProviderStatusService $providerStatusService,
    ) {
    }

    /**
     * @return array{synced: int, accounts: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function syncGoogleAccounts(): array
    {
        return $this->syncAccounts(['google']);
    }

    /**
     * @param array<int, string> $providers
     *
     * @return array{synced: int, accounts: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function syncAccounts(array $providers = ['google', 'microsoft', 'linkedin', 'openai']): array
    {
        $result = [
            'synced'   => 0,
            'accounts' => [],
            'errors'   => [],
        ];

        // Bootstrap credentials from the integration settings. These were previously
        // unguarded, so a single provider failing here aborted the whole sync — every
        // other provider (including OpenAI) never got a chance to run.
        $bootstraps = [
            'microsoft' => $this->microsoftAdsProvider,
            'openai'    => $this->openAiAdsProvider,
        ];

        foreach ($bootstraps as $providerName => $providerService) {
            if (!in_array($providerName, $providers, true)) {
                continue;
            }

            if (!$this->providerStatusService->isProviderEnabled($providerName)) {
                continue;
            }

            try {
                $providerService->syncCredentialFromIntegration();
            } catch (\Throwable $e) {
                $result['errors'][] = sprintf('%s (credential): %s', $providerName, $e->getMessage());
            }
        }

        $credentials = $this->entityManager->getRepository(AdsProviderCredential::class)->findBy([
            'status'   => 'active',
        ]);

        foreach ($credentials as $credential) {
            if (!$credential instanceof AdsProviderCredential) {
                continue;
            }

            if (!in_array($credential->getProvider(), $providers, true)) {
                continue;
            }

            if (!$this->providerStatusService->isProviderEnabled($credential->getProvider())) {
                continue;
            }

            $provider = $this->getProvider($credential->getProvider());
            if (null === $provider) {
                $result['errors'][] = sprintf('No provider configured for %s.', $credential->getProvider());
                continue;
            }

            try {
                $accounts = $provider->syncAccounts($credential);
            } catch (\Throwable $e) {
                $result['errors'][] = sprintf('%s: %s', $credential->getProvider(), $e->getMessage());
                continue;
            }

            foreach ($accounts as $accountData) {
                $this->upsertAccount($credential, $accountData);
                ++$result['synced'];
                $result['accounts'][] = $accountData;
            }

            $this->entityManager->flush();
        }

        return $result;
    }

    private function getProvider(string $provider): GoogleAdsProvider|MicrosoftAdsProvider|LinkedInAdsProvider|OpenAiAdsProvider|null
    {
        return match ($provider) {
            'google'    => $this->googleAdsProvider,
            'microsoft' => $this->microsoftAdsProvider,
            'linkedin'  => $this->linkedInAdsProvider,
            'openai'    => $this->openAiAdsProvider,
            default     => null,
        };
    }

    /**
     * @param array<string, mixed> $accountData
     */
    private function upsertAccount(AdsProviderCredential $credential, array $accountData): void
    {
        $externalAccountId = (string) ($accountData['externalAccountId'] ?? '');
        if ('' === $externalAccountId) {
            return;
        }

        $account = $this->entityManager->getRepository(AdsAccount::class)->findOneBy([
            'provider'          => $credential->getProvider(),
            'externalAccountId' => $externalAccountId,
        ]);

        if (!$account instanceof AdsAccount) {
            $account = new AdsAccount();
            $account->setProvider($credential->getProvider())
                ->setExternalAccountId($externalAccountId)
                ->setCreatedAt(new \DateTime());
        }

        $account->setCredentialId((int) $credential->getId())
            ->setExternalManagerId($accountData['externalManagerId'] ?? null)
            ->setName((string) ($accountData['name'] ?? $credential->getProvider().' Ads '.$externalAccountId))
            ->setCurrency($accountData['currency'] ?? null)
            ->setTimezone($accountData['timezone'] ?? null)
            ->setStatus((string) ($accountData['status'] ?? 'active'))
            ->setLastSyncAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($account);
    }
}
