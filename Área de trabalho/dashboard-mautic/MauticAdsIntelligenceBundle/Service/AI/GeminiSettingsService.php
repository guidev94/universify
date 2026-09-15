<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;

class GeminiSettingsService
{
    public const DEFAULT_MODEL = CreativeModelCatalog::DEFAULT_TEXT_MODEL;

    private const INTEGRATION_NAME   = 'AdsIntelligence';
    private const API_KEY_KEY        = 'gemini_api_key';
    private const MODEL_KEY          = 'gemini_model';
    private const SYSTEM_PROMPT_KEYS = [
        CreativeModelCatalog::MODE_TEXT  => 'creative_system_prompt_text',
        CreativeModelCatalog::MODE_IMAGE => 'creative_system_prompt_image',
        CreativeModelCatalog::MODE_VIDEO => 'creative_system_prompt_video',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EncryptionHelper $encryptionHelper,
    ) {
    }

    /**
     * @return array{
     *     configured: bool,
     *     hasApiKey: bool,
     *     apiKey: ?string,
     *     maskedApiKey: ?string,
     *     model: string,
     *     systemPrompts: array{text: string, image: string, video: string},
     *     systemPromptIsDefault: array{text: bool, image: bool, video: bool}
     * }
     */
    public function getSettings(bool $includeApiKey = false): array
    {
        $keys   = $this->getDecryptedApiKeys();
        $apiKey = $this->normalizeScalarSetting($keys[self::API_KEY_KEY] ?? null);
        $model  = trim($this->normalizeScalarSetting($keys[self::MODEL_KEY] ?? self::DEFAULT_MODEL));

        if ('' === $model) {
            $model = self::DEFAULT_MODEL;
        }

        $systemPrompts         = [];
        $systemPromptIsDefault = [];
        foreach (self::SYSTEM_PROMPT_KEYS as $mode => $key) {
            $storedPrompt = $this->normalizeScalarSetting($keys[$key] ?? null);
            $isDefault    = '' === $storedPrompt;

            $systemPrompts[$mode]         = $isDefault ? CreativeSystemPromptCatalog::defaultFor($mode) : $storedPrompt;
            $systemPromptIsDefault[$mode] = $isDefault;
        }

        return [
            'configured'            => '' !== $apiKey,
            'hasApiKey'             => '' !== $apiKey,
            'apiKey'                => $includeApiKey ? $apiKey : null,
            'maskedApiKey'          => '' !== $apiKey ? $this->maskApiKey($apiKey) : null,
            'model'                 => $model,
            'systemPrompts'         => $systemPrompts,
            'systemPromptIsDefault' => $systemPromptIsDefault,
        ];
    }

    /**
     * @param array{text?: string, image?: string, video?: string} $systemPrompts
     */
    public function save(?string $apiKey, string $model, array $systemPrompts = []): void
    {
        $integration = $this->getOrCreateIntegration();
        $keys        = $this->getDecryptedApiKeys($integration);

        $apiKey = trim((string) $apiKey);
        $model  = trim($model);

        if (mb_strlen($apiKey) > 512) {
            throw new \InvalidArgumentException('Gemini API key exceeds the allowed length.');
        }
        if ('' !== $model && (mb_strlen($model) > 128 || 1 !== preg_match('/^[a-zA-Z0-9._\/-]+$/', $model))) {
            throw new \InvalidArgumentException('Gemini model name is invalid.');
        }

        if ('' !== $apiKey) {
            $keys[self::API_KEY_KEY] = $apiKey;
        }

        $keys[self::MODEL_KEY] = '' !== $model ? $model : self::DEFAULT_MODEL;

        foreach (self::SYSTEM_PROMPT_KEYS as $mode => $key) {
            if (!array_key_exists($mode, $systemPrompts)) {
                continue;
            }

            $prompt = trim((string) ($systemPrompts[$mode] ?? ''));
            if (mb_strlen($prompt) > CreativeSystemPromptCatalog::MAX_LENGTH) {
                throw new \InvalidArgumentException(sprintf('System prompt for %s exceeds the allowed length.', $mode));
            }

            if ('' === $prompt || CreativeSystemPromptCatalog::defaultFor($mode) === $prompt) {
                unset($keys[$key]);
                continue;
            }

            $keys[$key] = $prompt;
        }

        $integration->setApiKeys($this->encryptApiKeys($keys));
        $this->entityManager->persist($integration);
        $this->entityManager->flush();
    }

    public function getSystemPrompt(string $mode): string
    {
        if (!CreativeModelCatalog::isValidMode($mode)) {
            throw new \InvalidArgumentException('Unsupported creative mode.');
        }

        return $this->getSettings()['systemPrompts'][$mode];
    }

    public function clearApiKey(): void
    {
        $integration = $this->getOrCreateIntegration();
        $keys        = $this->getDecryptedApiKeys($integration);
        unset($keys[self::API_KEY_KEY]);

        $integration->setApiKeys($this->encryptApiKeys($keys));
        $this->entityManager->persist($integration);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function getDecryptedApiKeys(?Integration $integration = null): array
    {
        $integration ??= $this->getIntegration();

        if (!$integration instanceof Integration) {
            return [];
        }

        $keys = $integration->getApiKeys();
        if (!is_array($keys)) {
            return [];
        }

        $decrypted = [];
        foreach ($keys as $key => $value) {
            if (!is_string($value) || '' === $value) {
                $decrypted[$key] = $value;
                continue;
            }

            if (!str_contains($value, '|')) {
                $decrypted[$key] = $value;
                continue;
            }

            try {
                $decoded = $this->encryptionHelper->decrypt($value);
            } catch (\TypeError) {
                $decrypted[$key] = null;
                continue;
            }

            $decrypted[$key] = false !== $decoded ? $decoded : $value;
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
            if (null === $value) {
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $encrypted[$key] = $this->encryptionHelper->encrypt((string) $value);
        }

        return $encrypted;
    }

    private function normalizeScalarSetting(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function getIntegration(): ?Integration
    {
        return $this->entityManager->getRepository(Integration::class)->findOneBy([
            'name' => self::INTEGRATION_NAME,
        ]);
    }

    private function getOrCreateIntegration(): Integration
    {
        $integration = $this->getIntegration();

        if (!$integration instanceof Integration) {
            $integration = new Integration();
            $integration->setName(self::INTEGRATION_NAME);
            $integration->setIsPublished(false);
            $integration->setSupportedFeatures([]);
            $integration->setFeatureSettings([]);
            $integration->setApiKeys([]);
        }

        return $integration;
    }

    private function maskApiKey(string $apiKey): string
    {
        if (strlen($apiKey) <= 8) {
            return str_repeat('*', strlen($apiKey));
        }

        return substr($apiKey, 0, 4).str_repeat('*', max(4, strlen($apiKey) - 8)).substr($apiKey, -4);
    }
}
