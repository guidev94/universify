<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsConversionQueue;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\AdsProviderInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution\AuditService;
use Psr\Log\LoggerInterface;

class OfflineConversionSyncService
{
    /** @var array<string, AdsProviderInterface> */
    private array $providers = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OfflineConversionQueueService $queueService,
        private readonly AuditService $auditService,
        private readonly LoggerInterface $logger,
        private readonly OfflineConversionSettingsService $settingsService,
        AdsProviderInterface ...$providers,
    ) {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    public function registerProvider(AdsProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Processes all pending conversions in the queue.
     *
     * @return array{processed: int, sent: int, failed: int, errors: array<int, string>}
     */
    public function sync(): array
    {
        $pendingByProvider = $this->queueService->getPendingGroupedByProvider();

        $processed = 0;
        $sent      = 0;
        $failed    = 0;
        $errors    = [];

        foreach ($pendingByProvider as $providerName => $conversions) {
            $provider = $this->providers[$providerName] ?? null;

            if ($provider === null) {
                $errors[] = sprintf('No provider registered for "%s".', $providerName);
                $this->logger->warning(sprintf(
                    'AdsIntelligence: no provider registered for "%s", skipping %d conversions',
                    $providerName,
                    count($conversions)
                ));
                $failed += count($conversions);
                continue;
            }

            if (!$provider->supportsOfflineConversions()) {
                $this->logger->info(sprintf(
                    'AdsIntelligence: provider "%s" does not support offline conversions',
                    $providerName
                ));
                continue;
            }

            // Group by account
            $byAccount = $this->groupByAccount($conversions);

            foreach ($byAccount as $accountId => $accountConversions) {
                $account = $this->em->getRepository(AdsAccount::class)->find($accountId);

                if ($account === null) {
                    $failed += count($accountConversions);
                    continue;
                }

                $processed += count($accountConversions);

                $payload = $this->buildPayload($accountConversions);

                try {
                    $result = $provider->uploadOfflineConversions($account, $payload);

                    $sent   += $result['uploaded'] ?? 0;
                    $failed += $result['failed'] ?? 0;

                    if ($result['success']) {
                        foreach ($accountConversions as $conv) {
                            $this->queueService->markSent($conv);
                        }

                        $this->auditService->log(
                            null,
                            $providerName,
                            null,
                            'offline_conversions_sent',
                            sprintf('Sent %d conversions to %s account %d', count($accountConversions), $providerName, $accountId)
                        );
                    } else {
                        foreach ($result['errors'] ?? [] as $error) {
                            $errors[] = (string) $error;
                        }

                        foreach ($accountConversions as $conv) {
                            $this->queueService->markFailed($conv, implode('; ', $result['errors'] ?? []));
                        }
                    }
                } catch (\Throwable $e) {
                    $errors[] = $e->getMessage();

                    $this->logger->error(sprintf(
                        'AdsIntelligence: failed to upload conversions to %s: %s',
                        $providerName,
                        $e->getMessage()
                    ));

                    foreach ($accountConversions as $conv) {
                        $this->queueService->markFailed($conv, $e->getMessage());
                    }

                    $failed += count($accountConversions);
                }
            }
        }

        return ['processed' => $processed, 'sent' => $sent, 'failed' => $failed, 'errors' => array_values(array_unique($errors))];
    }

    /**
     * @param AdsConversionQueue[] $conversions
     *
     * @return array<int, AdsConversionQueue[]>
     */
    private function groupByAccount(array $conversions): array
    {
        $grouped = [];

        foreach ($conversions as $conv) {
            $grouped[$conv->getAccountId()][] = $conv;
        }

        return $grouped;
    }

    /**
     * Builds the payload array for the provider upload method.
     *
     * @param AdsConversionQueue[] $conversions
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildPayload(array $conversions): array
    {
        $payload = [];
        $settings = $this->settingsService->getSettings();

        foreach ($conversions as $conv) {
            $conversionPayload = $conv->getPayload() ?? [];
            if ('google' === $conv->getProvider() && empty($conversionPayload['google_conversion_action'])) {
                $conversionPayload['google_conversion_action'] = $settings['google_conversion_action'];
            }

            $payload[] = [
                'conversion_name'     => $conv->getConversionName(),
                'conversion_value'    => $conv->getConversionValue(),
                'conversion_currency' => $conv->getConversionCurrency(),
                'conversion_time'     => $conv->getConversionTime()->format(\DateTimeInterface::ATOM),
                'click_id'            => $conv->getClickId(),
                'click_id_type'       => $conv->getClickIdType(),
                'hashed_email'        => $conv->getHashedEmail(),
                'hashed_phone'        => $conv->getHashedPhone(),
                'payload'             => $conversionPayload,
            ];
        }

        return $payload;
    }
}
