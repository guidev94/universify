<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * LinkedIn Ads provider using the LinkedIn Marketing REST API.
 *
 * Supports multiple LinkedIn accounts: each OAuth authorization stores its own
 * AdsProviderCredential row, and every AdsAccount points back to the credential
 * it was discovered through.
 */
class LinkedInAdsProvider extends AbstractAdsProvider
{
    private const API_VERSION = '202608';
    private const BASE_URL    = 'https://api.linkedin.com/rest';
    private const TOKEN_URL   = 'https://www.linkedin.com/oauth/v2/accessToken';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EncryptionHelper $encryptionHelper,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

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
        $code         = (string) ($credentials['code'] ?? '');
        $redirectUri  = (string) ($credentials['redirect_uri'] ?? '');
        $clientId     = (string) ($credentials['client_id'] ?? '');
        $clientSecret = (string) ($credentials['client_secret'] ?? '');

        if ('' === $code || '' === $clientId || '' === $clientSecret) {
            return $this->errorResult('Missing required credentials: code, client_id, client_secret');
        }

        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'redirect_uri'  => $redirectUri,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
                'timeout' => 30,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: LinkedIn OAuth exchange failed: '.$e->getMessage());

            return $this->errorResult('LinkedIn OAuth exchange failed: '.$e->getMessage());
        }

        if (!empty($payload['error'])) {
            $message = (string) ($payload['error_description'] ?? $payload['error']);
            $this->logger->error('AdsIntelligence: LinkedIn OAuth error: '.$message);

            return $this->errorResult('LinkedIn OAuth error: '.$message);
        }

        $accessToken = (string) ($payload['access_token'] ?? '');
        if ('' === $accessToken) {
            return $this->errorResult('LinkedIn OAuth response missing access_token');
        }

        $expiresIn = (int) ($payload['expires_in'] ?? 3600);

        return [
            'success'       => true,
            'access_token'  => $accessToken,
            'refresh_token' => (string) ($payload['refresh_token'] ?? ''),
            'expires_at'    => time() + $expiresIn,
            'expires_in'    => $expiresIn,
        ];
    }

    public function refreshToken(AdsProviderCredential $credential): array
    {
        $clientId     = (string) ($credential->getClientId() ?? '');
        $clientSecret = $this->decrypt($credential->getClientSecretEncrypted()) ?? '';
        $refreshToken = $this->decrypt($credential->getRefreshTokenEncrypted()) ?? '';

        if ('' === $clientId || '' === $clientSecret || '' === $refreshToken) {
            return $this->errorResult('Missing LinkedIn OAuth client_id, client_secret or refresh_token');
        }

        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
                'timeout' => 30,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: LinkedIn token refresh failed: '.$e->getMessage());

            return $this->errorResult('LinkedIn token refresh failed: '.$e->getMessage());
        }

        if (!empty($payload['error'])) {
            $message = (string) ($payload['error_description'] ?? $payload['error']);
            $this->logger->error('AdsIntelligence: LinkedIn refresh error: '.$message);

            return $this->errorResult('LinkedIn refresh error: '.$message);
        }

        $newAccessToken = (string) ($payload['access_token'] ?? '');
        if ('' === $newAccessToken) {
            return $this->errorResult('LinkedIn refresh response missing access_token');
        }

        $newRefreshToken = (string) ($payload['refresh_token'] ?? $refreshToken);
        $expiresIn       = (int) ($payload['expires_in'] ?? 3600);

        $credential->setAccessTokenEncrypted($this->encryptionHelper->encrypt($newAccessToken))
            ->setRefreshTokenEncrypted($this->encryptionHelper->encrypt($newRefreshToken))
            ->setTokenExpiresAt((new \DateTime())->modify(sprintf('+%d seconds', $expiresIn)))
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($credential);
        $this->entityManager->flush();

        return $this->successResult('Token refreshed', [
            'access_token' => $newAccessToken,
            'expires_at'   => time() + $expiresIn,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function syncAccounts(AdsProviderCredential $credential): array
    {
        if ('' === $this->getAccessToken($credential)) {
            return [];
        }

        try {
            $payload = $this->apiRequest(
                'GET',
                '/adAccounts?q=search&search=(status:(values:List(ACTIVE)))&count=100',
                $credential
            );
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: LinkedIn syncAccounts failed: '.$e->getMessage());

            return [];
        }

        $accounts = [];
        foreach (($payload['elements'] ?? []) as $element) {
            if (!is_array($element)) {
                continue;
            }

            $accountId = (string) ($element['id'] ?? '');
            if ('' === $accountId) {
                continue;
            }

            $accounts[] = [
                'externalAccountId' => $accountId,
                'name'              => (string) ($element['name'] ?? 'LinkedIn Ads '.$accountId),
                'currency'          => isset($element['currency']) ? (string) $element['currency'] : null,
                'timezone'          => null,
                'status'            => 'active',
            ];
        }

        return $accounts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function syncCampaigns(AdsAccount $account): array
    {
        $credential = $this->getCredentialForAccount($account);
        if (null === $credential) {
            return [];
        }

        $accountId = $account->getExternalAccountId();

        try {
            $payload = $this->apiRequest(
                'GET',
                sprintf('/adAccounts/%s/adCampaigns?q=search&search=(status:(values:List(ACTIVE,PAUSED,DRAFT)))&count=100', $accountId),
                $credential
            );
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: LinkedIn syncCampaigns failed: '.$e->getMessage());

            return [];
        }

        $campaigns = [];
        foreach (($payload['elements'] ?? []) as $element) {
            if (!is_array($element)) {
                continue;
            }

            $campaignId = (string) ($element['id'] ?? '');
            if ('' === $campaignId) {
                continue;
            }

            $budget = null;
            if (isset($element['dailyBudget']['amount'])) {
                $budget = (float) $element['dailyBudget']['amount'];
            } elseif (isset($element['totalBudget']['amount'])) {
                $budget = (float) $element['totalBudget']['amount'];
            }

            $campaigns[] = [
                'externalCampaignId' => $campaignId,
                'name'               => (string) ($element['name'] ?? 'Campaign '.$campaignId),
                'status'             => strtolower((string) ($element['status'] ?? 'active')),
                'budget'             => $budget,
                'type'               => (string) ($element['type'] ?? ''),
                'costType'           => (string) ($element['costType'] ?? ''),
            ];
        }

        return $campaigns;
    }

    /**
     * LinkedIn groups campaigns under Campaign Groups rather than ad groups.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncAdGroups(AdsAccount $account): array
    {
        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function syncAds(AdsAccount $account): array
    {
        return [];
    }

    /**
     * LinkedIn does not use keyword targeting.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncKeywords(AdsAccount $account): array
    {
        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function syncMetrics(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $credential = $this->getCredentialForAccount($account);
        if (null === $credential) {
            return [];
        }

        $accountUrn = urlencode('urn:li:sponsoredAccount:'.$account->getExternalAccountId());

        $dateRange = sprintf(
            '(start:(year:%d,month:%d,day:%d),end:(year:%d,month:%d,day:%d))',
            (int) $from->format('Y'),
            (int) $from->format('n'),
            (int) $from->format('j'),
            (int) $to->format('Y'),
            (int) $to->format('n'),
            (int) $to->format('j')
        );

        $url = sprintf(
            '/adAnalytics?q=analytics&pivot=CAMPAIGN&timeGranularity=DAILY&dateRange=%s&accounts=List(%s)'
            .'&fields=impressions,clicks,costInLocalCurrency,externalWebsiteConversions,dateRange,pivotValues',
            $dateRange,
            $accountUrn
        );

        try {
            $payload = $this->apiRequest('GET', $url, $credential);
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: LinkedIn syncMetrics failed: '.$e->getMessage());

            return [];
        }

        $metrics = [];
        foreach (($payload['elements'] ?? []) as $element) {
            if (!is_array($element)) {
                continue;
            }

            $start = $element['dateRange']['start'] ?? null;
            if (!is_array($start)) {
                continue;
            }

            $date = sprintf(
                '%04d-%02d-%02d',
                (int) ($start['year'] ?? 0),
                (int) ($start['month'] ?? 0),
                (int) ($start['day'] ?? 0)
            );

            $externalCampaignId = '';
            $campaignUrn = (string) ($element['pivotValues'][0] ?? '');
            if (1 === preg_match('/sponsoredCampaign:(\d+)/', $campaignUrn, $matches)) {
                $externalCampaignId = $matches[1];
            }

            $metrics[] = [
                'date'               => $date,
                'externalCampaignId' => $externalCampaignId,
                'impressions'        => (int) ($element['impressions'] ?? 0),
                'clicks'             => (int) ($element['clicks'] ?? 0),
                'cost'               => (float) ($element['costInLocalCurrency'] ?? 0),
                'conversions'        => (float) ($element['externalWebsiteConversions'] ?? 0),
                'conversionValue'    => 0.0,
            ];
        }

        return $metrics;
    }

    /**
     * Returns a usable access token, refreshing it when it is close to expiry.
     */
    private function getAccessToken(AdsProviderCredential $credential): string
    {
        $expiresAt = $credential->getTokenExpiresAt();

        if ($expiresAt && $expiresAt < new \DateTime('+5 minutes')) {
            $result = $this->refreshToken($credential);

            if (!($result['success'] ?? false)) {
                $this->logger->warning(sprintf(
                    'AdsIntelligence: LinkedIn token refresh failed for credential #%s',
                    (string) $credential->getId()
                ));

                return '';
            }

            return (string) ($result['data']['access_token'] ?? '');
        }

        return $this->decrypt($credential->getAccessTokenEncrypted()) ?? '';
    }

    private function getCredentialForAccount(AdsAccount $account): ?AdsProviderCredential
    {
        $credentialId = $account->getCredentialId();
        if (!$credentialId) {
            return null;
        }

        $credential = $this->entityManager->getRepository(AdsProviderCredential::class)->find($credentialId);

        return $credential instanceof AdsProviderCredential ? $credential : null;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function apiRequest(string $method, string $url, AdsProviderCredential $credential, array $options = []): array
    {
        $accessToken = $this->getAccessToken($credential);
        if ('' === $accessToken) {
            throw new \RuntimeException(sprintf(
                'No valid access token available for LinkedIn credential #%s',
                (string) $credential->getId()
            ));
        }

        $fullUrl = str_starts_with($url, 'http') ? $url : self::BASE_URL.$url;

        $options['headers'] = array_merge([
            'Authorization'             => 'Bearer '.$accessToken,
            'LinkedIn-Version'          => self::API_VERSION,
            'X-Restli-Protocol-Version' => '2.0.0',
        ], $options['headers'] ?? []);
        $options['timeout'] = $options['timeout'] ?? 30;

        $response   = $this->httpClient->request($method, $fullUrl, $options);
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            $body = $response->getContent(false);
            $this->logger->error(sprintf('AdsIntelligence: LinkedIn API %d: %s', $statusCode, $body));

            throw new \RuntimeException(sprintf('LinkedIn API returned HTTP %d: %s', $statusCode, $body));
        }

        return $response->toArray(false);
    }

    private function decrypt(?string $encrypted): ?string
    {
        if (null === $encrypted || '' === $encrypted) {
            return null;
        }

        // Values not produced by EncryptionHelper have no "<payload>|<iv>" shape.
        if (!str_contains($encrypted, '|')) {
            return null;
        }

        try {
            $decrypted = $this->encryptionHelper->decrypt($encrypted);
        } catch (\Throwable) {
            return null;
        }

        return false !== $decrypted ? (string) $decrypted : null;
    }
}
