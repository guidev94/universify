<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Integration;

/**
 * Plugin-side override so non-scalar API key values never hit core EncryptionHelper.
 */
trait SafeApiKeyEncryptionTrait
{
    /**
     * @param array<string, mixed> $keys
     *
     * @return array<string, string>
     */
    public function encryptApiKeys(array $keys)
    {
        $encrypted = [];

        foreach ($keys as $name => $key) {
            if (null === $key) {
                continue;
            }

            if (!is_scalar($key)) {
                $encoded = json_encode($key);
                if (false === $encoded) {
                    continue;
                }
                $key = $encoded;
            }

            $encrypted[$name] = $this->encryptionHelper->encrypt((string) $key);
        }

        return $encrypted;
    }

    /**
     * @param array<string, mixed> $keys
     * @param bool                 $mainDecryptOnly
     *
     * @return array<string, mixed>
     */
    public function decryptApiKeys(array $keys, $mainDecryptOnly = false)
    {
        $decrypted = [];

        foreach ($keys as $name => $key) {
            if (!is_string($key) || '' === $key) {
                $decrypted[$name] = $key;
                continue;
            }

            // Core EncryptionHelper::decrypt() explodes on "<payload>|<iv>"; anything
            // without that separator was never encrypted by it, so pass it through.
            if (!str_contains($key, '|')) {
                $decrypted[$name] = $key;
                continue;
            }

            try {
                $decoded = $this->encryptionHelper->decrypt($key, $mainDecryptOnly);
            } catch (\TypeError) {
                $decrypted[$name] = $key;
                continue;
            }

            if (false === $decoded) {
                continue;
            }

            $decrypted[$name] = $decoded;
        }

        return $decrypted;
    }
}
