<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;

class OfflineConversionSettingsService
{
    public const DEFAULT_CONVERSION_NAME = 'Lead convertido';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EncryptionHelper $encryptionHelper,
    ) {
    }

    /**
     * @return array{tag_ids: array<int>, conversion_name: string, conversion_value: float, currency: string, google_conversion_action: string, bing_feed_username: string, bing_feed_password_configured: bool}
     */
    public function getSettings(): array
    {
        $keys = $this->getApiKeys();

        $tagIds = array_values(array_filter(array_map(
            static fn (mixed $tagId): int => (int) $tagId,
            (array) ($keys['offline_conversion_tag_ids'] ?? [])
        )));

        return [
            'tag_ids'                  => $tagIds,
            'conversion_name'          => (string) ($keys['offline_conversion_name'] ?? self::DEFAULT_CONVERSION_NAME),
            'conversion_value'         => (float) ($keys['offline_conversion_value'] ?? 0.0),
            'currency'                 => strtoupper((string) ($keys['offline_conversion_currency'] ?? 'BRL')),
            'google_conversion_action' => (string) ($keys['google_offline_conversion_action'] ?? ''),
            'bing_feed_username'            => (string) ($keys['bing_offline_conversion_feed_username'] ?? ''),
            'bing_feed_password_configured' => '' !== (string) ($keys['bing_offline_conversion_feed_password'] ?? ''),
        ];
    }

    /**
     * @return array{username: string, password: string}
     */
    public function getCsvFeedCredentials(): array
    {
        $keys = $this->getApiKeys();

        return [
            'username' => (string) ($keys['bing_offline_conversion_feed_username'] ?? ''),
            'password' => (string) ($keys['bing_offline_conversion_feed_password'] ?? ''),
        ];
    }

    /**
     * @param array<int> $tagIds
     */
    public function save(
        array $tagIds,
        string $conversionName,
        float $conversionValue,
        string $currency,
        string $googleConversionAction,
        ?string $bingFeedUsername = null,
        ?string $bingFeedPassword = null,
    ): void
    {
        $integration = $this->getIntegration();
        $keys        = $this->getApiKeys($integration);

        $keys['offline_conversion_tag_ids'] = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        $keys['offline_conversion_name'] = '' !== trim($conversionName) ? trim($conversionName) : self::DEFAULT_CONVERSION_NAME;
        $keys['offline_conversion_value'] = $conversionValue;
        $keys['offline_conversion_currency'] = strtoupper(trim($currency) ?: 'BRL');
        $keys['google_offline_conversion_action'] = trim($googleConversionAction);
        if (null !== $bingFeedUsername) {
            $keys['bing_offline_conversion_feed_username'] = trim($bingFeedUsername);
        }
        if (null !== $bingFeedPassword && '' !== trim($bingFeedPassword)) {
            $keys['bing_offline_conversion_feed_password'] = trim($bingFeedPassword);
        }

        $integration->setApiKeys($this->encryptApiKeys($keys));
        $this->em->persist($integration);
        $this->em->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function getApiKeys(?Integration $integration = null): array
    {
        $integration ??= $this->getIntegration();
        $apiKeys = $integration->getApiKeys();

        if (empty($apiKeys) || !is_array($apiKeys)) {
            return [];
        }

        $decrypted      = [];
        $needsReencrypt = false;
        foreach ($apiKeys as $key => $value) {
            if (!is_string($value) || '' === $value) {
                $decrypted[$key] = $value;
                $needsReencrypt  = $needsReencrypt || null !== $value;
                continue;
            }

            $decoded = $this->encryptionHelper->decrypt($value);
            $decrypted[$key] = false !== $decoded ? $decoded : $value;
        }

        if ($needsReencrypt) {
            $integration->setApiKeys($this->encryptApiKeys($decrypted));
            $this->em->persist($integration);
            $this->em->flush();
        }

        return $decrypted;
    }

    /**
     * @param array<string, mixed> $keys
     *
     * @return array<string, string>
     */
    private function encryptApiKeys(array $keys): array
    {
        $encrypted = [];
        foreach ($keys as $key => $value) {
            if (null === $value || !is_scalar($value)) {
                continue;
            }

            $encrypted[$key] = $this->encryptionHelper->encrypt((string) $value);
        }

        return $encrypted;
    }

    private function getIntegration(): Integration
    {
        $integration = $this->em->getRepository(Integration::class)->findOneBy(['name' => 'AdsIntelligence']);

        if (!$integration instanceof Integration) {
            $integration = new Integration();
            $integration->setName('AdsIntelligence');
            $integration->setIsPublished(false);
            $integration->setSupportedFeatures([]);
            $integration->setFeatureSettings([]);
            $integration->setApiKeys([]);
        }

        return $integration;
    }
}
