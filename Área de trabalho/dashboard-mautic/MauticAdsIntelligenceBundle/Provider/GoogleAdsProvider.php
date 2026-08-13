<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAction;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAdGroup;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsKeyword;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Google Ads provider stub.
 *
 * Full implementation requires:
 * - composer require googleads/google-ads-php
 * - OAuth2 flow setup
 * - Customer ID / Manager Account ID config
 */
class GoogleAdsProvider extends AbstractAdsProvider
{
    private const API_VERSION = 'v24';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EncryptionHelper $encryptionHelper,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getName(): string
    {
        return 'google';
    }

    public function getLabel(): string
    {
        return 'Google Ads';
    }

    public function supportsOfflineConversions(): bool
    {
        return true;
    }

    public function supportsCampaignMutations(): bool
    {
        return true;
    }

    public function supportsBudgetMutations(): bool
    {
        return true;
    }

    public function authenticate(array $credentials): array
    {
        // TODO: implement OAuth2 authorization code flow with Google Ads API
        throw new \RuntimeException('GoogleAdsProvider::authenticate() not yet implemented');
    }

    public function refreshToken(AdsProviderCredential $credential): array
    {
        $keys = $this->getAdsIntelligenceApiKeys();
        $clientId = (string) ($credential->getClientId() ?? $keys['client_id'] ?? '');
        $clientSecret = (string) ($this->decrypt($credential->getClientSecretEncrypted()) ?? $keys['client_secret'] ?? '');
        $refreshToken = (string) ($this->decrypt($credential->getRefreshTokenEncrypted()) ?? $keys['refresh_token'] ?? '');

        if ('' === $clientId || '' === $clientSecret || '' === $refreshToken) {
            return $this->errorResult('Missing Google OAuth client_id, client_secret or refresh_token');
        }

        try {
            $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                ],
                'timeout' => 30,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: Google OAuth refresh failed: '.$e->getMessage());

            return $this->errorResult('Google OAuth refresh failed: '.$e->getMessage());
        }

        if (!empty($payload['error'])) {
            $message = is_array($payload['error']) ? (string) ($payload['error']['message'] ?? 'OAuth refresh failed') : (string) $payload['error'];

            return $this->errorResult($message, $payload);
        }

        if (empty($payload['access_token'])) {
            return $this->errorResult('Google OAuth refresh did not return an access token', $payload);
        }

        $expiresAt = new \DateTime(sprintf('+%d seconds', (int) ($payload['expires_in'] ?? 3600)));

        $credential->setAccessTokenEncrypted($this->encryptionHelper->encrypt((string) $payload['access_token']))
            ->setTokenExpiresAt($expiresAt)
            ->setStatus('active')
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($credential);
        $this->entityManager->flush();

        return $this->successResult('Google OAuth token refreshed', [
            'access_token' => (string) $payload['access_token'],
            'expires_at'   => $expiresAt,
        ]);
    }

    public function syncAccounts(AdsProviderCredential $credential): array
    {
        $accessToken = $this->getValidAccessToken($credential);
        if (null === $accessToken) {
            throw new \RuntimeException('Unable to obtain a valid Google Ads access token.');
        }

        $keys = $this->getAdsIntelligenceApiKeys();
        $developerToken = (string) ($keys['developer_token'] ?? '');
        $loginCustomerId = $this->normalizeCustomerId((string) ($keys['login_customer_id'] ?? ''));

        if ('' === $developerToken) {
            throw new \RuntimeException('Google Ads developer token is not configured.');
        }

        $headers = $this->getGoogleAdsHeaders($accessToken, $developerToken, $loginCustomerId);
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers:listAccessibleCustomers', self::API_VERSION);

        try {
            $response = $this->httpClient->request('GET', $endpoint, [
                'headers' => $headers,
                'timeout' => 30,
            ]);
            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: Google Ads listAccessibleCustomers failed: '.$e->getMessage());

            throw new \RuntimeException('Google Ads listAccessibleCustomers failed: '.$e->getMessage(), 0, $e);
        }

        if (!empty($payload['error'])) {
            $message = (string) ($payload['error']['message'] ?? 'Google Ads API returned an error');
            throw new \RuntimeException($message);
        }

        $resourceNames = $payload['resourceNames'] ?? [];
        if (!is_array($resourceNames)) {
            return [];
        }

        $accounts = [];
        foreach ($resourceNames as $resourceName) {
            if (!is_string($resourceName) || !str_starts_with($resourceName, 'customers/')) {
                continue;
            }

            $customerId = $this->normalizeCustomerId(substr($resourceName, 10));
            if ('' === $customerId) {
                continue;
            }

            $account = $this->fetchCustomerDetails($customerId, $headers, $loginCustomerId);
            if (null === $account) {
                continue;
            }

            $accounts[] = $account;
        }

        return $accounts;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchCustomerDetails(string $customerId, array $headers, string $loginCustomerId): ?array
    {
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/googleAds:search', self::API_VERSION, $customerId);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $headers,
                'json' => [
                    'query' => 'SELECT customer.id, customer.descriptive_name, customer.currency_code, customer.time_zone, customer.status, customer.manager FROM customer LIMIT 1',
                ],
                'timeout' => 30,
            ]);
            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf('AdsIntelligence: failed to fetch Google Ads customer %s details: %s', $customerId, $e->getMessage()));

            return null;
        }

        if (!empty($payload['error'])) {
            $this->logger->warning(sprintf('AdsIntelligence: Google Ads customer %s details error: %s', $customerId, $payload['error']['message'] ?? 'unknown error'));

            return null;
        }

        $customer = $payload['results'][0]['customer'] ?? [];
        if (!is_array($customer)) {
            $this->logger->warning(sprintf('AdsIntelligence: Google Ads customer %s returned no readable details and will not be registered.', $customerId));

            return null;
        }

        return [
            'externalAccountId' => $this->normalizeCustomerId((string) ($customer['id'] ?? $customerId)),
            'externalManagerId' => '' !== $loginCustomerId ? $loginCustomerId : null,
            'name'              => (string) ($customer['descriptiveName'] ?? 'Google Ads '.$customerId),
            'currency'          => $customer['currencyCode'] ?? null,
            'timezone'          => $customer['timeZone'] ?? null,
            'status'            => strtolower((string) ($customer['status'] ?? 'active')),
            'manager'           => (bool) ($customer['manager'] ?? false),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getGoogleAdsHeaders(string $accessToken, string $developerToken, string $loginCustomerId): array
    {
        $headers = [
            'Authorization'   => 'Bearer '.$accessToken,
            'developer-token' => $developerToken,
            'Content-Type'    => 'application/json',
        ];

        if ('' !== $loginCustomerId) {
            $headers['login-customer-id'] = $loginCustomerId;
        }

        return $headers;
    }

    private function getValidAccessToken(AdsProviderCredential $credential): ?string
    {
        $accessToken = $this->decrypt($credential->getAccessTokenEncrypted());
        $expiresAt = $credential->getTokenExpiresAt();

        if ($accessToken && (!$expiresAt || $expiresAt > new \DateTime('+2 minutes'))) {
            return $accessToken;
        }

        $refresh = $this->refreshToken($credential);
        if (!($refresh['success'] ?? false)) {
            $this->logger->error('AdsIntelligence: unable to refresh Google Ads token: '.($refresh['message'] ?? 'unknown error'));

            return null;
        }

        return $refresh['data']['access_token'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getAdsIntelligenceApiKeys(): array
    {
        $integration = $this->entityManager->getRepository(Integration::class)->findOneBy([
            'name' => 'AdsIntelligence',
        ]);

        if (!$integration instanceof Integration || !is_array($integration->getApiKeys())) {
            return [];
        }

        $keys = [];
        foreach ($integration->getApiKeys() as $key => $value) {
            if (!is_string($value) || '' === $value) {
                $keys[$key] = $value;
                continue;
            }

            $decrypted = $this->encryptionHelper->decrypt($value);
            $keys[$key] = false !== $decrypted ? $decrypted : $value;
        }

        return $keys;
    }

    private function decrypt(?string $encrypted): ?string
    {
        if (null === $encrypted || '' === $encrypted) {
            return null;
        }

        $decrypted = $this->encryptionHelper->decrypt($encrypted);

        return false !== $decrypted ? (string) $decrypted : null;
    }

    private function normalizeCustomerId(string $customerId): string
    {
        return preg_replace('/\D+/', '', $customerId) ?? '';
    }

    public function syncCampaigns(AdsAccount $account): array
    {
        $credential = $this->entityManager->getRepository(AdsProviderCredential::class)->find((int) $account->getCredentialId());
        if (!$credential instanceof AdsProviderCredential) {
            throw new \RuntimeException(sprintf('Credential #%d not found for account %s.', $account->getCredentialId(), $account->getExternalAccountId()));
        }

        $accessToken = $this->getValidAccessToken($credential);
        if (null === $accessToken) {
            throw new \RuntimeException('Unable to obtain a valid Google Ads access token.');
        }

        $keys = $this->getAdsIntelligenceApiKeys();
        $developerToken = (string) ($keys['developer_token'] ?? '');
        $loginCustomerId = $this->normalizeCustomerId((string) ($keys['login_customer_id'] ?? $account->getExternalManagerId() ?? ''));

        if ('' === $developerToken) {
            throw new \RuntimeException('Google Ads developer token is not configured.');
        }

        $customerId = $this->normalizeCustomerId($account->getExternalAccountId());
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/googleAds:search', self::API_VERSION, $customerId);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $this->getGoogleAdsHeaders($accessToken, $developerToken, $loginCustomerId),
                'json' => [
                    'query' => 'SELECT campaign.id, campaign.name, campaign.status, campaign.advertising_channel_type FROM campaign ORDER BY campaign.id',
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('AdsIntelligence: Google Ads campaign sync failed for account %s: %s', $customerId, $e->getMessage()));

            throw new \RuntimeException(sprintf('Google Ads campaign sync failed for account %s: %s', $customerId, $e->getMessage()), 0, $e);
        }

        if (!empty($payload['error'])) {
            throw new \RuntimeException($this->formatGoogleAdsError($payload['error'], 'Google Ads API returned an error while syncing campaigns.'));
        }

        $results = $payload['results'] ?? [];
        if (!is_array($results)) {
            return [];
        }

        $campaigns = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $campaign = $result['campaign'] ?? [];
            if (!is_array($campaign)) {
                continue;
            }

            $campaigns[] = [
                'accountId'          => (int) $account->getId(),
                'externalCampaignId' => (string) ($campaign['id'] ?? ''),
                'name'               => (string) ($campaign['name'] ?? 'Google Ads Campaign'),
                'objective'          => null,
                'channelType'        => isset($campaign['advertisingChannelType']) ? strtolower((string) $campaign['advertisingChannelType']) : null,
                'status'             => isset($campaign['status']) ? strtolower((string) $campaign['status']) : null,
                'budgetAmount'       => null,
                'budgetType'         => null,
                'startedAt'          => null,
                'endedAt'            => null,
            ];
        }

        return $campaigns;
    }

    /**
     * Creates a Google Ads Search campaign in PAUSED state.
     *
     * @param array{name: string, dailyBudget: float} $data
     *
     * @return array{externalCampaignId: string, resourceName: string, budgetResourceName: string, name: string, status: string, channelType: string, budgetAmount: float, budgetType: string}
     */
    public function createPausedCampaign(AdsAccount $account, array $data): array
    {
        $credential = $this->entityManager->getRepository(AdsProviderCredential::class)->find((int) $account->getCredentialId());
        if (!$credential instanceof AdsProviderCredential) {
            throw new \RuntimeException(sprintf('Credential #%d not found for account %s.', $account->getCredentialId(), $account->getExternalAccountId()));
        }

        $accessToken = $this->getValidAccessToken($credential);
        if (null === $accessToken) {
            throw new \RuntimeException('Unable to obtain a valid Google Ads access token.');
        }

        $keys = $this->getAdsIntelligenceApiKeys();
        $developerToken = (string) ($keys['developer_token'] ?? '');
        $loginCustomerId = $this->normalizeCustomerId((string) ($keys['login_customer_id'] ?? $account->getExternalManagerId() ?? ''));

        if ('' === $developerToken) {
            throw new \RuntimeException('Google Ads developer token is not configured.');
        }

        $customerId = $this->normalizeCustomerId($account->getExternalAccountId());
        $headers = $this->getGoogleAdsHeaders($accessToken, $developerToken, $loginCustomerId);
        $name = trim($data['name']);
        $dailyBudget = max(1.0, (float) $data['dailyBudget']);
        $budgetResourceName = $this->createCampaignBudget($customerId, $headers, $name, $dailyBudget);

        $campaignResourceName = $this->createSearchCampaign($customerId, $headers, $name, $budgetResourceName);

        return [
            'externalCampaignId' => $this->extractResourceId($campaignResourceName),
            'resourceName'       => $campaignResourceName,
            'budgetResourceName' => $budgetResourceName,
            'name'               => $name,
            'status'             => 'paused',
            'channelType'        => 'search',
            'budgetAmount'       => $dailyBudget,
            'budgetType'         => 'daily',
        ];
    }

    /**
     * @param array<string, string> $headers
     */
    private function createCampaignBudget(string $customerId, array $headers, string $campaignName, float $dailyBudget): string
    {
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/campaignBudgets:mutate', self::API_VERSION, $customerId);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $headers,
                'json' => [
                    'operations' => [[
                        'create' => [
                            'name'             => sprintf('%s Budget %s', $campaignName, bin2hex(random_bytes(3))),
                            'amountMicros'     => (string) ((int) round($dailyBudget * 1000000)),
                            'deliveryMethod'   => 'STANDARD',
                            'explicitlyShared' => false,
                        ],
                    ]],
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Google Ads campaign budget creation failed: '.$e->getMessage(), 0, $e);
        }

        if (!empty($payload['error'])) {
            $message = $this->formatGoogleAdsError($payload['error'], 'Google Ads campaign budget creation failed.');
            $this->logger->error('AdsIntelligence: '.$message);

            throw new \RuntimeException($message);
        }

        $resourceName = $payload['results'][0]['resourceName'] ?? null;
        if (!is_string($resourceName) || '' === $resourceName) {
            throw new \RuntimeException('Google Ads campaign budget creation returned no resource name.');
        }

        return $resourceName;
    }

    /**
     * @param array<string, string> $headers
     */
    private function createSearchCampaign(string $customerId, array $headers, string $name, string $budgetResourceName): string
    {
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/campaigns:mutate', self::API_VERSION, $customerId);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $headers,
                'json' => [
                    'operations' => [[
                        'create' => [
                            'name'                         => sprintf('%s %s', $name, bin2hex(random_bytes(3))),
                            'advertisingChannelType'       => 'SEARCH',
                            'status'                       => 'PAUSED',
                            'manualCpc'                    => new \stdClass(),
                            'campaignBudget'               => $budgetResourceName,
                            'containsEuPoliticalAdvertising'=> 'DOES_NOT_CONTAIN_EU_POLITICAL_ADVERTISING',
                            'networkSettings'              => [
                                'targetGoogleSearch'         => true,
                                'targetSearchNetwork'        => true,
                                'targetPartnerSearchNetwork' => false,
                                'targetContentNetwork'       => false,
                            ],
                        ],
                    ]],
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Google Ads campaign creation failed: '.$e->getMessage(), 0, $e);
        }

        if (!empty($payload['error'])) {
            $message = $this->formatGoogleAdsError($payload['error'], 'Google Ads campaign creation failed.');
            $this->logger->error('AdsIntelligence: '.$message);

            throw new \RuntimeException($message);
        }

        $resourceName = $payload['results'][0]['resourceName'] ?? null;
        if (!is_string($resourceName) || '' === $resourceName) {
            throw new \RuntimeException('Google Ads campaign creation returned no resource name.');
        }

        return $resourceName;
    }

    private function extractResourceId(string $resourceName): string
    {
        $parts = explode('/', $resourceName);

        return (string) end($parts);
    }

    /**
     * @param mixed $error
     */
    private function formatGoogleAdsError(mixed $error, string $fallback): string
    {
        if (!is_array($error)) {
            return $fallback;
        }

        $message = (string) ($error['message'] ?? $fallback);
        if (!empty($error['details'])) {
            $details = json_encode($error['details'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (is_string($details)) {
                $message .= ' Details: '.$details;
            }
        }

        return $message;
    }

    public function syncAdGroups(AdsAccount $account): array
    {
        $context = $this->getSearchContext($account);
        $rows = $this->searchGoogleAds($context['endpoint'], $context['headers'], 'SELECT campaign.id, ad_group.id, ad_group.name, ad_group.status FROM ad_group ORDER BY campaign.id, ad_group.id');

        $adGroups = [];
        foreach ($rows as $row) {
            $campaign = $row['campaign'] ?? [];
            $adGroup = $row['adGroup'] ?? [];

            if (!is_array($campaign) || !is_array($adGroup)) {
                continue;
            }

            $externalAdGroupId = (string) ($adGroup['id'] ?? '');
            if ('' === $externalAdGroupId) {
                continue;
            }

            $adGroups[] = [
                'externalCampaignId' => (string) ($campaign['id'] ?? ''),
                'externalAdgroupId'  => $externalAdGroupId,
                'name'               => (string) ($adGroup['name'] ?? 'Google Ads Ad Group '.$externalAdGroupId),
                'status'             => isset($adGroup['status']) ? strtolower((string) $adGroup['status']) : null,
            ];
        }

        return $adGroups;
    }

    public function syncAds(AdsAccount $account): array
    {
        // TODO: use Google Ads API AdService
        return [];
    }

    public function syncKeywords(AdsAccount $account): array
    {
        $context = $this->getSearchContext($account);
        $keywords = [];

        $adGroupRows = $this->searchGoogleAds(
            $context['endpoint'],
            $context['headers'],
            'SELECT campaign.id, ad_group.id, ad_group_criterion.criterion_id, ad_group_criterion.keyword.text, ad_group_criterion.keyword.match_type, ad_group_criterion.status, ad_group_criterion.negative FROM keyword_view WHERE ad_group_criterion.type = KEYWORD AND ad_group_criterion.status != REMOVED ORDER BY campaign.id, ad_group.id, ad_group_criterion.criterion_id'
        );

        foreach ($adGroupRows as $row) {
            $keyword = $this->keywordFromAdGroupCriterionRow($row);
            if (null !== $keyword) {
                $keywords[] = $keyword;
            }
        }

        $campaignRows = $this->searchGoogleAds(
            $context['endpoint'],
            $context['headers'],
            'SELECT campaign.id, campaign_criterion.criterion_id, campaign_criterion.keyword.text, campaign_criterion.keyword.match_type, campaign_criterion.status, campaign_criterion.negative FROM campaign_criterion WHERE campaign_criterion.type = KEYWORD AND campaign_criterion.negative = TRUE AND campaign_criterion.status != REMOVED ORDER BY campaign.id, campaign_criterion.criterion_id'
        );

        foreach ($campaignRows as $row) {
            $keyword = $this->keywordFromCampaignCriterionRow($row);
            if (null !== $keyword) {
                $keywords[] = $keyword;
            }
        }

        return $keywords;
    }

    /**
     * @return array{externalKeywordId: string, resourceName: string, keywordText: string, matchType: string, negative: bool, status: string}
     */
    public function createCampaignNegativeKeyword(AdsAccount $account, AdsCampaign $campaign, string $keywordText, string $matchType): array
    {
        $context = $this->getSearchContext($account);
        $customerId = $this->normalizeCustomerId($account->getExternalAccountId());
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/campaignCriteria:mutate', self::API_VERSION, $customerId);
        $matchType = $this->normalizeKeywordMatchType($matchType);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $context['headers'],
                'json' => [
                    'operations' => [[
                        'create' => [
                            'campaign' => sprintf('customers/%s/campaigns/%s', $customerId, $campaign->getExternalCampaignId()),
                            'negative' => true,
                            'keyword'  => [
                                'text'      => $keywordText,
                                'matchType' => $matchType,
                            ],
                        ],
                    ]],
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Google Ads negative keyword creation failed: '.$e->getMessage(), 0, $e);
        }

        if (!empty($payload['error'])) {
            throw new \RuntimeException($this->formatGoogleAdsError($payload['error'], 'Google Ads negative keyword creation failed.'));
        }

        $resourceName = $payload['results'][0]['resourceName'] ?? null;
        if (!is_string($resourceName) || '' === $resourceName) {
            throw new \RuntimeException('Google Ads negative keyword creation returned no resource name.');
        }

        return [
            'externalKeywordId' => $this->extractCriterionId($resourceName),
            'resourceName'      => $resourceName,
            'keywordText'       => $keywordText,
            'matchType'         => strtolower($matchType),
            'negative'          => true,
            'status'            => 'enabled',
        ];
    }

    /**
     * @return array{externalKeywordId: string, resourceName: string, keywordText: string, matchType: string, negative: bool, status: string}
     */
    public function createAdGroupKeyword(AdsAccount $account, AdsCampaign $campaign, AdsAdGroup $adGroup, string $keywordText, string $matchType): array
    {
        $context = $this->getSearchContext($account);
        $customerId = $this->normalizeCustomerId($account->getExternalAccountId());
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/adGroupCriteria:mutate', self::API_VERSION, $customerId);
        $matchType = $this->normalizeKeywordMatchType($matchType);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $context['headers'],
                'json' => [
                    'operations' => [[
                        'create' => [
                            'adGroup' => sprintf('customers/%s/adGroups/%s', $customerId, $adGroup->getExternalAdgroupId()),
                            'status'  => 'ENABLED',
                            'keyword' => [
                                'text'      => $keywordText,
                                'matchType' => $matchType,
                            ],
                        ],
                    ]],
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Google Ads keyword creation failed: '.$e->getMessage(), 0, $e);
        }

        if (!empty($payload['error'])) {
            throw new \RuntimeException($this->formatGoogleAdsError($payload['error'], 'Google Ads keyword creation failed.'));
        }

        $resourceName = $payload['results'][0]['resourceName'] ?? null;
        if (!is_string($resourceName) || '' === $resourceName) {
            throw new \RuntimeException('Google Ads keyword creation returned no resource name.');
        }

        return [
            'externalKeywordId' => $this->extractCriterionId($resourceName),
            'resourceName'      => $resourceName,
            'keywordText'       => $keywordText,
            'matchType'         => strtolower($matchType),
            'negative'          => false,
            'status'            => 'enabled',
        ];
    }

    public function removeKeyword(AdsAccount $account, AdsCampaign $campaign, AdsKeyword $keyword, ?AdsAdGroup $adGroup): void
    {
        if (null === $keyword->getExternalKeywordId()) {
            throw new \RuntimeException('Keyword has no external criterion id.');
        }

        $context = $this->getSearchContext($account);
        $customerId = $this->normalizeCustomerId($account->getExternalAccountId());
        $criterionId = $this->normalizeGoogleAdsId($keyword->getExternalKeywordId());
        if ('' === $criterionId) {
            throw new \RuntimeException('Keyword has an invalid external criterion id.');
        }

        if ($keyword->isNegative() && !$adGroup instanceof AdsAdGroup) {
            $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/campaignCriteria:mutate', self::API_VERSION, $customerId);
            $campaignId = $this->normalizeGoogleAdsId($campaign->getExternalCampaignId());
            if ('' === $campaignId) {
                throw new \RuntimeException('Campaign has an invalid Google Ads campaign id.');
            }

            $resourceName = $this->resolveCampaignCriterionResourceName($context['endpoint'], $context['headers'], $campaignId, $criterionId);
            if (null === $resourceName) {
                return;
            }
        } else {
            if (!$adGroup instanceof AdsAdGroup) {
                throw new \RuntimeException('Ad group keyword requires an ad group.');
            }

            $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/adGroupCriteria:mutate', self::API_VERSION, $customerId);
            $adGroupId = $this->normalizeGoogleAdsId($adGroup->getExternalAdgroupId());
            if ('' === $adGroupId) {
                throw new \RuntimeException('Ad group has an invalid Google Ads ad group id.');
            }

            $resourceName = $this->resolveAdGroupCriterionResourceName($context['endpoint'], $context['headers'], $adGroupId, $criterionId);
            if (null === $resourceName) {
                return;
            }
        }

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $context['headers'],
                'json' => [
                    'operations' => [[
                        'remove' => $resourceName,
                    ]],
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Google Ads keyword removal failed: '.$e->getMessage(), 0, $e);
        }

        if (!empty($payload['error'])) {
            throw new \RuntimeException($this->formatGoogleAdsError($payload['error'], 'Google Ads keyword removal failed.'));
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function resolveCampaignCriterionResourceName(string $endpoint, array $headers, string $campaignId, string $criterionId): ?string
    {
        $rows = $this->searchGoogleAds($endpoint, $headers, sprintf(
            'SELECT campaign_criterion.resource_name FROM campaign_criterion WHERE campaign.id = %s AND campaign_criterion.criterion_id = %s AND campaign_criterion.type = KEYWORD AND campaign_criterion.negative = TRUE AND campaign_criterion.status != REMOVED LIMIT 1',
            $campaignId,
            $criterionId,
        ));

        $resourceName = $rows[0]['campaignCriterion']['resourceName'] ?? null;
        if (!is_string($resourceName) || '' === $resourceName) {
            return null;
        }

        return $resourceName;
    }

    /**
     * @param array<string, string> $headers
     */
    private function resolveAdGroupCriterionResourceName(string $endpoint, array $headers, string $adGroupId, string $criterionId): ?string
    {
        $rows = $this->searchGoogleAds($endpoint, $headers, sprintf(
            'SELECT ad_group_criterion.resource_name FROM ad_group_criterion WHERE ad_group.id = %s AND ad_group_criterion.criterion_id = %s AND ad_group_criterion.type = KEYWORD AND ad_group_criterion.status != REMOVED LIMIT 1',
            $adGroupId,
            $criterionId,
        ));

        $resourceName = $rows[0]['adGroupCriterion']['resourceName'] ?? null;
        if (!is_string($resourceName) || '' === $resourceName) {
            return null;
        }

        return $resourceName;
    }

    private function normalizeKeywordMatchType(string $matchType): string
    {
        $matchType = strtoupper(trim($matchType));

        return in_array($matchType, ['BROAD', 'PHRASE', 'EXACT'], true) ? $matchType : 'BROAD';
    }

    private function extractCriterionId(string $resourceName): string
    {
        $parts = explode('~', $resourceName);

        return (string) end($parts);
    }

    private function normalizeGoogleAdsId(string $id): string
    {
        $id = trim($id);
        if (str_contains($id, '/')) {
            $parts = explode('/', $id);
            $id = (string) end($parts);
        }

        if (str_contains($id, '~')) {
            $parts = explode('~', $id);
            $id = (string) end($parts);
        }

        return preg_replace('/\D+/', '', $id) ?? '';
    }

    /**
     * @return array{endpoint: string, headers: array<string, string>}
     */
    private function getSearchContext(AdsAccount $account): array
    {
        $credential = $this->entityManager->getRepository(AdsProviderCredential::class)->find((int) $account->getCredentialId());
        if (!$credential instanceof AdsProviderCredential) {
            throw new \RuntimeException(sprintf('Credential #%d not found for account %s.', $account->getCredentialId(), $account->getExternalAccountId()));
        }

        $accessToken = $this->getValidAccessToken($credential);
        if (null === $accessToken) {
            throw new \RuntimeException('Unable to obtain a valid Google Ads access token.');
        }

        $keys = $this->getAdsIntelligenceApiKeys();
        $developerToken = (string) ($keys['developer_token'] ?? '');
        $loginCustomerId = $this->normalizeCustomerId((string) ($keys['login_customer_id'] ?? $account->getExternalManagerId() ?? ''));

        if ('' === $developerToken) {
            throw new \RuntimeException('Google Ads developer token is not configured.');
        }

        $customerId = $this->normalizeCustomerId($account->getExternalAccountId());

        return [
            'endpoint' => sprintf('https://googleads.googleapis.com/%s/customers/%s/googleAds:search', self::API_VERSION, $customerId),
            'headers'  => $this->getGoogleAdsHeaders($accessToken, $developerToken, $loginCustomerId),
        ];
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<int, array<string, mixed>>
     */
    private function searchGoogleAds(string $endpoint, array $headers, string $query): array
    {
        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $headers,
                'json' => [
                    'query' => $query,
                ],
                'timeout' => 60,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: Google Ads search failed: '.$e->getMessage());

            throw new \RuntimeException('Google Ads search failed: '.$e->getMessage(), 0, $e);
        }

        if (!empty($payload['error'])) {
            throw new \RuntimeException($this->formatGoogleAdsError($payload['error'], 'Google Ads API returned an error while searching.'));
        }

        $results = $payload['results'] ?? [];

        return is_array($results) ? $results : [];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null
     */
    private function keywordFromAdGroupCriterionRow(array $row): ?array
    {
        $campaign = $row['campaign'] ?? [];
        $adGroup = $row['adGroup'] ?? [];
        $criterion = $row['adGroupCriterion'] ?? [];
        $keyword = is_array($criterion) ? ($criterion['keyword'] ?? []) : [];

        if (!is_array($campaign) || !is_array($adGroup) || !is_array($criterion) || !is_array($keyword)) {
            return null;
        }

        $keywordText = trim((string) ($keyword['text'] ?? ''));
        if ('' === $keywordText) {
            return null;
        }

        return [
            'externalCampaignId' => (string) ($campaign['id'] ?? ''),
            'externalAdgroupId'  => (string) ($adGroup['id'] ?? ''),
            'externalKeywordId'  => (string) ($criterion['criterionId'] ?? ''),
            'keywordText'        => $keywordText,
            'matchType'          => isset($keyword['matchType']) ? strtolower((string) $keyword['matchType']) : null,
            'status'             => isset($criterion['status']) ? strtolower((string) $criterion['status']) : null,
            'negative'           => (bool) ($criterion['negative'] ?? false),
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null
     */
    private function keywordFromCampaignCriterionRow(array $row): ?array
    {
        $campaign = $row['campaign'] ?? [];
        $criterion = $row['campaignCriterion'] ?? [];
        $keyword = is_array($criterion) ? ($criterion['keyword'] ?? []) : [];

        if (!is_array($campaign) || !is_array($criterion) || !is_array($keyword)) {
            return null;
        }

        $keywordText = trim((string) ($keyword['text'] ?? ''));
        if ('' === $keywordText) {
            return null;
        }

        return [
            'externalCampaignId' => (string) ($campaign['id'] ?? ''),
            'externalAdgroupId'  => null,
            'externalKeywordId'  => (string) ($criterion['criterionId'] ?? ''),
            'keywordText'        => $keywordText,
            'matchType'          => isset($keyword['matchType']) ? strtolower((string) $keyword['matchType']) : null,
            'status'             => isset($criterion['status']) ? strtolower((string) $criterion['status']) : null,
            'negative'           => true,
        ];
    }

    public function syncMetrics(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $context = $this->getSearchContext($account);
        $query = sprintf(
            "SELECT segments.date, metrics.clicks, metrics.impressions, metrics.conversions, metrics.cost_micros FROM customer WHERE segments.date BETWEEN '%s' AND '%s'",
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        );

        return $this->searchGoogleAds($context['endpoint'], $context['headers'], $query);
    }

    public function fetchClickPerformance(AdsAccount $account, array $filters): array
    {
        $context = $this->getSearchContext($account);
        $query = "SELECT segments.date, campaign.name, metrics.clicks, metrics.impressions, metrics.conversions, metrics.cost_micros FROM campaign WHERE segments.date DURING LAST_30_DAYS";

        if (!empty($filters['campaign_id'])) {
            $query .= " AND campaign.id = " . (int)$filters['campaign_id'];
        }

        return $this->searchGoogleAds($context['endpoint'], $context['headers'], $query);
    }

    public function fetchKeywordPerformance(AdsAccount $account, array $filters): array
    {
        $context = $this->getSearchContext($account);
        $query = "SELECT ad_group_criterion.keyword.text, ad_group_criterion.quality_info.quality_score, ad_group_criterion.keyword.match_type, ad_group_criterion.status, campaign.name, metrics.clicks, metrics.impressions, metrics.ctr, metrics.cost_micros FROM keyword_view WHERE segments.date DURING LAST_30_DAYS";

        if (!empty($filters['campaign_id'])) {
            $query .= " AND campaign.id = " . (int)$filters['campaign_id'];
        }

        return $this->searchGoogleAds($context['endpoint'], $context['headers'], $query);
    }

    /**
     * @return array<int, array{account: string, resourceName: string, name: string, status: string, category: string, type: string}>
     */
    public function fetchConversionActions(AdsAccount $account): array
    {
        $context = $this->getSearchContext($account);
        $rows = $this->searchGoogleAds($context['endpoint'], $context['headers'], <<<'GAQL'
SELECT
  conversion_action.resource_name,
  conversion_action.name,
  conversion_action.status,
  conversion_action.category,
  conversion_action.type
FROM conversion_action
WHERE conversion_action.status != REMOVED
ORDER BY conversion_action.name
GAQL);

        $actions = [];
        foreach ($rows as $row) {
            $action = $row['conversionAction'] ?? [];
            if (!is_array($action) || empty($action['resourceName'])) {
                continue;
            }

            $actions[] = [
                'account'      => $account->getName(),
                'resourceName' => (string) $action['resourceName'],
                'name'         => (string) ($action['name'] ?? $action['resourceName']),
                'status'       => strtolower((string) ($action['status'] ?? '')),
                'category'     => strtolower((string) ($action['category'] ?? '')),
                'type'         => strtolower((string) ($action['type'] ?? '')),
            ];
        }

        return $actions;
    }

    /**
     * Creates an UPLOAD_CLICKS (offline import) conversion action in the given account.
     *
     * @return array{resourceName: string, name: string}
     */
    public function createOfflineConversionAction(AdsAccount $account, string $name): array
    {
        $name = trim($name);
        if ('' === $name) {
            throw new \RuntimeException('Conversion action name is required.');
        }

        $context = $this->getSearchContext($account);
        $customerId = $this->normalizeCustomerId($account->getExternalAccountId());
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s/conversionActions:mutate', self::API_VERSION, $customerId);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $context['headers'],
                'json'    => [
                    'operations' => [[
                        'create' => [
                            'name'         => $name,
                            'type'         => 'UPLOAD_CLICKS',
                            'category'     => 'DEFAULT',
                            'status'       => 'ENABLED',
                            'countingType' => 'ONE_PER_CLICK',
                            'valueSettings' => [
                                'defaultValue'              => 0,
                                'alwaysUseDefaultValue'     => false,
                                'defaultCurrencyCode'       => $account->getCurrency() ?: 'BRL',
                            ],
                        ],
                    ]],
                    'partialFailure' => false,
                ],
                'timeout' => 60,
            ]);
            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Google Ads conversion action creation failed: '.$e->getMessage(), 0, $e);
        }

        if (!empty($payload['error'])) {
            throw new \RuntimeException($this->formatGoogleAdsError($payload['error'], 'Google Ads conversion action creation failed.'));
        }

        $resourceName = (string) ($payload['results'][0]['resourceName'] ?? '');
        if ('' === $resourceName) {
            throw new \RuntimeException('Google Ads did not return the created conversion action resource name.');
        }

        return ['resourceName' => $resourceName, 'name' => $name];
    }

    public function uploadOfflineConversions(AdsAccount $account, array $conversions): array
    {
        $context = $this->getSearchContext($account);
        $customerId = $this->normalizeCustomerId($account->getExternalAccountId());
        $endpoint = sprintf('https://googleads.googleapis.com/%s/customers/%s:uploadClickConversions', self::API_VERSION, $customerId);

        $clickConversions = [];
        $errors = [];

        foreach ($conversions as $conversion) {
            $clickId = (string) ($conversion['click_id'] ?? '');
            $conversionAction = (string) (($conversion['payload']['google_conversion_action'] ?? '') ?: '');

            if ('' === $conversionAction) {
                $errors[] = 'Google conversion action is not configured.';
                continue;
            }

            if ('' === $clickId && empty($conversion['hashed_email']) && empty($conversion['hashed_phone'])) {
                $errors[] = 'Google conversion skipped: missing click id or hashed identifier.';
                continue;
            }

            $item = [
                'conversionAction'   => $conversionAction,
                'conversionDateTime' => $this->formatGoogleConversionTime((string) $conversion['conversion_time']),
                'conversionValue'    => (float) ($conversion['conversion_value'] ?? 0),
                'currencyCode'       => (string) ($conversion['conversion_currency'] ?? 'BRL'),
            ];

            $clickIdType = strtolower((string) ($conversion['click_id_type'] ?? 'gclid'));
            if ('' !== $clickId && in_array($clickIdType, ['gclid', 'gbraid', 'wbraid'], true)) {
                $item[$clickIdType] = $clickId;
            }

            $userIdentifiers = [];
            if (!empty($conversion['hashed_email'])) {
                $userIdentifiers[] = ['hashedEmail' => (string) $conversion['hashed_email']];
            }
            if (!empty($conversion['hashed_phone'])) {
                $userIdentifiers[] = ['hashedPhoneNumber' => (string) $conversion['hashed_phone']];
            }
            if ([] !== $userIdentifiers) {
                $item['userIdentifiers'] = $userIdentifiers;
            }

            $clickConversions[] = $item;
        }

        if ([] === $clickConversions) {
            return ['success' => false, 'uploaded' => 0, 'failed' => count($conversions), 'errors' => $errors ?: ['No valid Google conversions to upload.']];
        }

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $context['headers'],
                'json'    => [
                    'conversions'    => $clickConversions,
                    'partialFailure' => true,
                ],
                'timeout' => 60,
            ]);
            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            return ['success' => false, 'uploaded' => 0, 'failed' => count($conversions), 'errors' => [$e->getMessage()]];
        }

        if (!empty($payload['error'])) {
            return ['success' => false, 'uploaded' => 0, 'failed' => count($conversions), 'errors' => [$this->formatGoogleAdsError($payload['error'], 'Google offline conversion upload failed.')]];
        }

        if (!empty($payload['partialFailureError'])) {
            $errors[] = (string) ($payload['partialFailureError']['message'] ?? 'Partial failure uploading Google offline conversions.');
        }

        $uploaded = count($payload['results'] ?? []);
        $failed = max(0, count($conversions) - $uploaded);

        return ['success' => 0 === $failed, 'uploaded' => $uploaded, 'failed' => $failed, 'errors' => $errors];
    }

    private function formatGoogleConversionTime(string $value): string
    {
        return (new \DateTimeImmutable($value))->format('Y-m-d H:i:sO');
    }

    public function applyAction(AdsAction $action): array
    {
        // TODO: route to correct mutation service based on action type
        return $this->errorResult('Not yet implemented: '.$action->getActionType());
    }
}
