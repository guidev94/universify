<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdsIntelligenceIntegration extends AbstractIntegration
{
    use SafeApiKeyEncryptionTrait;

    public const NAME = 'AdsIntelligence';

    private const PROVIDER_GOOGLE = 'google';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Ads Intelligence';
    }

    public function getDescription(): string
    {
        return 'Integra dados de anúncios com CRM para otimização por IA.';
    }

    public function getAuthenticationType(): string
    {
        return 'oauth2';
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'client_id'         => 'mautic.ads_intelligence.google.client_id',
            'client_secret'     => 'mautic.ads_intelligence.google.client_secret',
            'developer_token'   => 'mautic.ads_intelligence.google.developer_token',
            'login_customer_id' => 'mautic.ads_intelligence.google.login_customer_id',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getSecretKeys(): array
    {
        return [
            'client_secret',
            'developer_token',
            'access_token',
            'refresh_token',
        ];
    }

    public function getAuthenticationUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    public function getAuthScope(): string
    {
        return 'https://www.googleapis.com/auth/adwords';
    }

    public function getAuthCallbackUrl(): string
    {
        return $this->router->generate(
            'mautic_integration_auth_callback_secure',
            ['integration' => $this->getName()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getAuthLoginUrl(): string
    {
        return parent::getAuthLoginUrl().'&access_type=offline&prompt=consent&include_granted_scopes=true';
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function getRefreshTokenKeys(): array
    {
        return ['refresh_token', 'expires_at'];
    }

    public function prepareResponseForExtraction($data)
    {
        if (is_array($data) && isset($data['expires_in'])) {
            $data['expires_at'] = time() + (int) $data['expires_in'];
        }

        return $this->filterTokenResponse($data);
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    private function filterTokenResponse($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        return array_filter(
            $data,
            static fn (mixed $value, string|int $key): bool => in_array($key, [
                'access_token',
                'refresh_token',
                'expires_in',
                'expires_at',
                'scope',
                'token_type',
            ], true) && (is_scalar($value) || null === $value),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function getBearerToken($inAuthorization = false)
    {
        if (!$inAuthorization && !empty($this->keys['access_token'])) {
            return $this->keys['access_token'];
        }

        return '';
    }

    public function authCallback($settings = [], $parameters = [])
    {
        $error = parent::authCallback($settings, $parameters);

        if (empty($error)) {
            $this->syncGoogleProviderCredential();
        }

        return $error;
    }

    public function syncGoogleProviderCredential(): void
    {
        $credential = $this->em->getRepository(AdsProviderCredential::class)->findOneBy([
            'provider' => self::PROVIDER_GOOGLE,
        ]);

        if (!$credential instanceof AdsProviderCredential) {
            $credential = new AdsProviderCredential();
            $credential->setProvider(self::PROVIDER_GOOGLE);
            $credential->setLabel('Google Ads OAuth');
        }

        $credential->setClientId($this->keys['client_id'] ?? null)
            ->setClientSecretEncrypted($this->encryptNullable($this->keys['client_secret'] ?? null))
            ->setAccessTokenEncrypted($this->encryptNullable($this->keys['access_token'] ?? null))
            ->setRefreshTokenEncrypted($this->encryptNullable($this->keys['refresh_token'] ?? null))
            ->setTokenExpiresAt($this->getTokenExpiresAt())
            ->setStatus(!empty($this->keys['refresh_token']) || !empty($this->keys['access_token']) ? 'active' : 'pending')
            ->setUpdatedAt(new \DateTime());

        $this->em->persist($credential);
        $this->em->flush();
    }

    private function encryptNullable(?string $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return $this->encryptionHelper->encrypt($value);
    }

    private function getTokenExpiresAt(): ?\DateTimeInterface
    {
        if (empty($this->keys['expires_at'])) {
            return null;
        }

        return (new \DateTime())->setTimestamp((int) $this->keys['expires_at']);
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedFeatures(): array
    {
        return [];
    }
}
