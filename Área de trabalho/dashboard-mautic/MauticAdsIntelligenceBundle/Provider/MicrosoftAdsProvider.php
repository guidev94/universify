<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAdGroup;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsKeyword;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredential;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MicrosoftAdsProvider extends AbstractAdsProvider
{
    private const CUSTOMER_WSDL = 'https://clientcenter.api.bingads.microsoft.com/Api/CustomerManagement/v13/CustomerManagementService.svc?singleWsdl';
    private const CAMPAIGN_WSDL = 'https://campaign.api.bingads.microsoft.com/Api/Advertiser/CampaignManagement/v13/CampaignManagementService.svc?singleWsdl';
    private const CUSTOMER_NS = 'https://bingads.microsoft.com/Customer/v13';
    private const CAMPAIGN_NS = 'https://bingads.microsoft.com/CampaignManagement/v13';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EncryptionHelper $encryptionHelper,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getName(): string
    {
        return 'microsoft';
    }

    public function getLabel(): string
    {
        return 'Microsoft Advertising';
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
        return $this->successResult('Microsoft OAuth is handled by the Mautic integration callback.');
    }

    public function syncCredentialFromIntegration(): ?AdsProviderCredential
    {
        $keys = $this->getIntegrationApiKeys();

        if ([] === $keys) {
            return null;
        }

        $credential = $this->entityManager->getRepository(AdsProviderCredential::class)->findOneBy([
            'provider' => 'microsoft',
        ]);

        if (!$credential instanceof AdsProviderCredential) {
            $credential = new AdsProviderCredential();
            $credential->setProvider('microsoft');
            $credential->setLabel('Microsoft Advertising OAuth');
            $credential->setCreatedAt(new \DateTime());
        }

        $credential->setClientId(isset($keys['client_id']) ? (string) $keys['client_id'] : null)
            ->setClientSecretEncrypted($this->encryptNullable($keys['client_secret'] ?? null))
            ->setAccessTokenEncrypted($this->encryptNullable($keys['access_token'] ?? null))
            ->setRefreshTokenEncrypted($this->encryptNullable($keys['refresh_token'] ?? null))
            ->setTokenExpiresAt($this->getExpiresAt($keys['expires_at'] ?? null))
            ->setStatus(!empty($keys['access_token']) || !empty($keys['refresh_token']) ? 'active' : 'pending')
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($credential);
        $this->entityManager->flush();

        return $credential;
    }

    public function refreshToken(AdsProviderCredential $credential): array
    {
        $keys = $this->getIntegrationApiKeys();
        $clientId = (string) ($credential->getClientId() ?? $keys['client_id'] ?? '');
        $clientSecret = (string) ($this->decrypt($credential->getClientSecretEncrypted()) ?? $keys['client_secret'] ?? '');
        $refreshToken = (string) ($this->decrypt($credential->getRefreshTokenEncrypted()) ?? $keys['refresh_token'] ?? '');

        if ('' === $clientId || '' === $clientSecret || '' === $refreshToken) {
            return $this->errorResult('Missing Microsoft OAuth client_id, client_secret or refresh_token');
        }

        try {
            $response = $this->httpClient->request('POST', 'https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'body' => [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                    'scope'         => 'https://ads.microsoft.com/msads.manage offline_access',
                ],
                'timeout' => 30,
            ]);

            $payload = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: Microsoft OAuth refresh failed: '.$e->getMessage());

            return $this->errorResult('Microsoft OAuth refresh failed: '.$e->getMessage());
        }

        if (!empty($payload['error'])) {
            return $this->errorResult((string) ($payload['error_description'] ?? $payload['error']));
        }

        if (empty($payload['access_token'])) {
            return $this->errorResult('Microsoft OAuth refresh did not return an access token', $payload);
        }

        $expiresAt = new \DateTime(sprintf('+%d seconds', (int) ($payload['expires_in'] ?? 3600)));

        $credential->setAccessTokenEncrypted($this->encryptionHelper->encrypt((string) $payload['access_token']))
            ->setTokenExpiresAt($expiresAt)
            ->setStatus('active')
            ->setUpdatedAt(new \DateTime());

        if (!empty($payload['refresh_token'])) {
            $credential->setRefreshTokenEncrypted($this->encryptionHelper->encrypt((string) $payload['refresh_token']));
        }

        $this->entityManager->persist($credential);
        $this->entityManager->flush();

        return $this->successResult('Microsoft OAuth token refreshed', [
            'access_token' => (string) $payload['access_token'],
            'expires_at'   => $expiresAt,
        ]);
    }

    public function syncAccounts(AdsProviderCredential $credential): array
    {
        $accessToken = $this->getValidAccessToken($credential);
        if (null === $accessToken) {
            throw new \RuntimeException('Unable to obtain a valid Microsoft Advertising access token.');
        }

        $developerToken = $this->getDeveloperToken();
        if ('' === $developerToken) {
            throw new \RuntimeException('Microsoft Advertising developer token is not configured.');
        }

        $client = $this->createSoapClient(self::CUSTOMER_WSDL, self::CUSTOMER_NS, $developerToken, $accessToken);
        $userResponse = $client->__soapCall('GetUser', [[
            'UserId' => null,
        ]]);

        $customerIds = [];
        foreach ($this->normalizeList($userResponse->CustomerRoles->CustomerRole ?? []) as $role) {
            $customerId = (string) ($role->CustomerId ?? '');
            if ('' !== $customerId) {
                $customerIds[$customerId] = $customerId;
            }
        }

        if ([] === $customerIds) {
            $this->logger->warning('AdsIntelligence: Microsoft Advertising GetUser returned no customer roles for the authenticated user.');
        }

        $accounts = [];
        foreach ($customerIds as $customerId) {
            $client = $this->createSoapClient(self::CUSTOMER_WSDL, self::CUSTOMER_NS, $developerToken, $accessToken, $customerId);
            $response = $client->__soapCall('GetAccountsInfo', [[
                'CustomerId'         => (int) $customerId,
                'OnlyParentAccounts' => false,
            ]]);

            foreach ($this->normalizeList($response->AccountsInfo->AccountInfo ?? []) as $accountInfo) {
                $accountId = (string) ($accountInfo->Id ?? '');
                if ('' === $accountId) {
                    continue;
                }

                $accounts[] = [
                    'externalAccountId' => $accountId,
                    'externalManagerId' => $customerId,
                    'name'              => (string) ($accountInfo->Name ?? 'Microsoft Ads '.$accountId),
                    'currency'          => isset($accountInfo->CurrencyCode) ? (string) $accountInfo->CurrencyCode : null,
                    'timezone'          => isset($accountInfo->TimeZone) ? (string) $accountInfo->TimeZone : null,
                    'status'            => strtolower((string) ($accountInfo->AccountLifeCycleStatus ?? 'active')),
                ];
            }
        }

        return $accounts;
    }

    public function syncCampaigns(AdsAccount $account): array
    {
        $credential = $this->entityManager->getRepository(AdsProviderCredential::class)->find((int) $account->getCredentialId());
        if (!$credential instanceof AdsProviderCredential) {
            throw new \RuntimeException(sprintf('Credential #%d not found for account %s.', $account->getCredentialId(), $account->getExternalAccountId()));
        }

        $accessToken = $this->getValidAccessToken($credential);
        if (null === $accessToken) {
            throw new \RuntimeException('Unable to obtain a valid Microsoft Advertising access token.');
        }

        $developerToken = $this->getDeveloperToken();
        if ('' === $developerToken) {
            throw new \RuntimeException('Microsoft Advertising developer token is not configured.');
        }

        $client = $this->createSoapClient(
            self::CAMPAIGN_WSDL,
            self::CAMPAIGN_NS,
            $developerToken,
            $accessToken,
            (string) $account->getExternalManagerId(),
            $account->getExternalAccountId(),
        );

        $campaigns = [];
        $campaignTypes = [
            'Search',
            'Shopping',
            'DynamicSearchAds',
            'Audience',
            'PerformanceMax',
            'Hotel',
        ];

        foreach ($campaignTypes as $campaignType) {
            try {
                $response = $client->__soapCall('GetCampaignsByAccountId', [[
                    'AccountId'    => (int) $account->getExternalAccountId(),
                    'CampaignType' => $campaignType,
                ]]);
            } catch (\SoapFault $e) {
                $this->logger->warning(sprintf('AdsIntelligence: Microsoft Advertising campaign type %s failed for account %s: %s', $campaignType, $account->getExternalAccountId(), $e->getMessage()));
                continue;
            }

            foreach ($this->normalizeList($response->Campaigns->Campaign ?? []) as $campaign) {
                $campaignId = (string) ($campaign->Id ?? '');
                if ('' === $campaignId || isset($campaigns[$campaignId])) {
                    continue;
                }

                $campaigns[$campaignId] = [
                    'accountId'          => (int) $account->getId(),
                    'externalCampaignId' => $campaignId,
                    'name'               => (string) ($campaign->Name ?? 'Microsoft Ads Campaign '.$campaignId),
                    'objective'          => null,
                    'channelType'        => isset($campaign->CampaignType) ? strtolower((string) $campaign->CampaignType) : strtolower($campaignType),
                    'status'             => isset($campaign->Status) ? strtolower((string) $campaign->Status) : null,
                    'budgetAmount'       => isset($campaign->DailyBudget) ? (float) $campaign->DailyBudget : null,
                    'budgetType'         => 'daily',
                    'startedAt'          => null,
                    'endedAt'            => null,
                ];
            }
        }

        if ([] === $campaigns) {
            $this->logger->warning(sprintf('AdsIntelligence: Microsoft Advertising account %s returned no campaigns for supported campaign types.', $account->getExternalAccountId()));
        }

        return array_values($campaigns);
    }

    public function syncAdGroups(AdsAccount $account): array
    {
        $client = $this->createCampaignClient($account);
        $adGroups = [];

        $campaigns = $this->entityManager->getRepository(AdsCampaign::class)->findBy([
            'provider'  => 'microsoft',
            'accountId' => (int) $account->getId(),
        ]);

        foreach ($campaigns as $campaign) {
            if (!$campaign instanceof AdsCampaign) {
                continue;
            }

            $response = $client->__soapCall('GetAdGroupsByCampaignId', [[
                'CampaignId' => (int) $campaign->getExternalCampaignId(),
            ]]);

            foreach ($this->soapList($response->AdGroups ?? null, 'AdGroup') as $adGroup) {
                $adGroupId = (string) ($adGroup->Id ?? '');
                if ('' === $adGroupId) {
                    continue;
                }

                $adGroups[] = [
                    'externalCampaignId' => $campaign->getExternalCampaignId(),
                    'externalAdgroupId'  => $adGroupId,
                    'name'               => (string) ($adGroup->Name ?? 'Microsoft Ads Ad Group '.$adGroupId),
                    'status'             => isset($adGroup->Status) ? strtolower((string) $adGroup->Status) : null,
                ];
            }
        }

        return $adGroups;
    }

    public function syncAds(AdsAccount $account): array
    {
        return [];
    }

    public function syncKeywords(AdsAccount $account): array
    {
        $client = $this->createCampaignClient($account);
        $keywords = [];

        $adGroups = $this->entityManager->getRepository(AdsAdGroup::class)->findBy([
            'provider'  => 'microsoft',
            'accountId' => (int) $account->getId(),
        ]);

        foreach ($adGroups as $adGroup) {
            if (!$adGroup instanceof AdsAdGroup) {
                continue;
            }

            $campaign = $this->entityManager->getRepository(AdsCampaign::class)->find((int) $adGroup->getCampaignId());
            if (!$campaign instanceof AdsCampaign) {
                continue;
            }

            $response = $client->__soapCall('GetKeywordsByAdGroupId', [[
                'AdGroupId' => (int) $adGroup->getExternalAdgroupId(),
            ]]);

            foreach ($this->soapList($response->Keywords ?? null, 'Keyword') as $keyword) {
                $keywordId = (string) ($keyword->Id ?? '');
                $keywordText = trim((string) ($keyword->Text ?? ''));
                if ('' === $keywordId || '' === $keywordText) {
                    continue;
                }

                $keywords[] = [
                    'externalCampaignId' => $campaign->getExternalCampaignId(),
                    'externalAdgroupId'  => $adGroup->getExternalAdgroupId(),
                    'externalKeywordId'  => $keywordId,
                    'keywordText'        => $keywordText,
                    'matchType'          => isset($keyword->MatchType) ? strtolower((string) $keyword->MatchType) : null,
                    'status'             => isset($keyword->Status) ? strtolower((string) $keyword->Status) : null,
                    'negative'           => false,
                ];
            }
        }

        $campaigns = $this->entityManager->getRepository(AdsCampaign::class)->findBy([
            'provider'  => 'microsoft',
            'accountId' => (int) $account->getId(),
        ]);

        foreach ($campaigns as $campaign) {
            if (!$campaign instanceof AdsCampaign) {
                continue;
            }

            $response = $client->__soapCall('GetNegativeKeywordsByEntityIds', [[
                'EntityIds' => ['long' => [(int) $campaign->getExternalCampaignId()]],
                'EntityType' => 'Campaign',
            ]]);

            foreach ($this->soapList($response->EntityNegativeKeywords ?? null, 'EntityNegativeKeyword') as $entityNegativeKeyword) {
                foreach ($this->soapList($entityNegativeKeyword->NegativeKeywords ?? null, 'NegativeKeyword') as $negativeKeyword) {
                    $keywordId = (string) ($negativeKeyword->Id ?? '');
                    $keywordText = trim((string) ($negativeKeyword->Text ?? ''));
                    if ('' === $keywordId || '' === $keywordText) {
                        continue;
                    }

                    $keywords[] = [
                        'externalCampaignId' => $campaign->getExternalCampaignId(),
                        'externalAdgroupId'  => null,
                        'externalKeywordId'  => $keywordId,
                        'keywordText'        => $keywordText,
                        'matchType'          => isset($negativeKeyword->MatchType) ? strtolower((string) $negativeKeyword->MatchType) : null,
                        'status'             => 'active',
                        'negative'           => true,
                    ];
                }
            }
        }

        return $keywords;
    }

    /**
     * @return array{externalKeywordId: string, keywordText: string, matchType: string, negative: bool, status: string}
     */
    public function createCampaignNegativeKeyword(AdsAccount $account, AdsCampaign $campaign, string $keywordText, string $matchType): array
    {
        $client = $this->createCampaignClient($account);
        $matchType = $this->normalizeNegativeMatchType($matchType);

        $response = $client->__soapCall('AddNegativeKeywordsToEntities', [[
            'EntityNegativeKeywords' => [
                'EntityNegativeKeyword' => [[
                    'EntityId' => (int) $campaign->getExternalCampaignId(),
                    'EntityType' => 'Campaign',
                    'NegativeKeywords' => [
                        'NegativeKeyword' => [[
                            'Text' => $keywordText,
                            'MatchType' => $matchType,
                        ]],
                    ],
                ]],
            ],
        ]]);

        $this->throwOnNestedPartialErrors($response->NestedPartialErrors ?? null, 'Microsoft negative keyword creation failed.');
        $keywordId = $this->firstLong($response->NegativeKeywordIds->IdCollection->Ids->long ?? null);
        if (null === $keywordId) {
            throw new \RuntimeException('Microsoft negative keyword creation returned no keyword id.');
        }

        return [
            'externalKeywordId' => (string) $keywordId,
            'keywordText'       => $keywordText,
            'matchType'         => strtolower($matchType),
            'negative'          => true,
            'status'            => 'active',
        ];
    }

    /**
     * Creates an Ad Group in Microsoft Advertising.
     *
     * @return array{id: string, name: string}
     */
    public function createAdGroup(AdsAccount $account, AdsCampaign $campaign, string $name): array
    {
        $name = trim($name);
        if ('' === $name) {
            throw new \RuntimeException('Ad Group name is required.');
        }

        $client = $this->createCampaignClient($account);

        $adGroup = [
            'Name' => $name,
            'Status' => 'Active',
            'AdGroupType' => 'SearchStandard',
            'CpcBid' => [
                'Amount' => 1.0,
            ],
            'Language' => 'Portuguese',
        ];

        try {
            $response = $client->__soapCall('AddAdGroups', [[
                'CampaignId' => (int) $campaign->getExternalCampaignId(),
                'AdGroups' => [
                    'AdGroup' => [
                        new \SoapVar($adGroup, SOAP_ENC_OBJECT, 'AdGroup', self::CAMPAIGN_NS),
                    ],
                ],
                'ReturnInheritedBidStrategyTypes' => false,
            ]]);
        } catch (\SoapFault $e) {
            $detail = isset($e->detail) ? json_encode($e->detail) : '';
            throw new \RuntimeException('Microsoft Advertising ad group creation failed: '.$e->getMessage() . ' Details: ' . $detail, 0, $e);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Microsoft Advertising ad group creation failed: '.$e->getMessage(), 0, $e);
        }

        $this->throwOnPartialErrors($response->PartialErrors ?? null, 'Microsoft ad group creation failed.');

        $ids = $this->normalizeList($response->AdGroupIds->long ?? []);
        $id = (string) ($ids[0] ?? '');

        if ('' === $id) {
            throw new \RuntimeException('Microsoft Advertising did not return the created ad group ID.');
        }

        return ['id' => $id, 'name' => $name];
    }

    /**
     * @return array{externalKeywordId: string, keywordText: string, matchType: string, negative: bool, status: string}
     */
    public function createAdGroupKeyword(AdsAccount $account, AdsCampaign $campaign, AdsAdGroup $adGroup, string $keywordText, string $matchType): array
    {
        $client = $this->createCampaignClient($account);
        $matchType = $this->normalizeMatchType($matchType);

        $response = $client->__soapCall('AddKeywords', [[
            'AdGroupId' => (int) $adGroup->getExternalAdgroupId(),
            'Keywords' => [
                'Keyword' => [[
                    'Text' => $keywordText,
                    'MatchType' => $matchType,
                    'Status' => 'Active',
                ]],
            ],
            'ReturnInheritedBidStrategyTypes' => false,
        ]]);

        $this->throwOnPartialErrors($response->PartialErrors ?? null, 'Microsoft keyword creation failed.');
        $keywordId = $this->firstLong($response->KeywordIds->long ?? null);
        if (null === $keywordId) {
            throw new \RuntimeException('Microsoft keyword creation returned no keyword id.');
        }

        return [
            'externalKeywordId' => (string) $keywordId,
            'keywordText'       => $keywordText,
            'matchType'         => strtolower($matchType),
            'negative'          => false,
            'status'            => 'active',
        ];
    }

    public function removeKeyword(AdsAccount $account, AdsCampaign $campaign, AdsKeyword $keyword, ?AdsAdGroup $adGroup): void
    {
        $client = $this->createCampaignClient($account);

        if (null === $keyword->getExternalKeywordId()) {
            throw new \RuntimeException('Keyword has no external id.');
        }

        if ($keyword->isNegative()) {
            $response = $client->__soapCall('DeleteNegativeKeywordsFromEntities', [[
                'EntityNegativeKeywords' => [
                    'EntityNegativeKeyword' => [[
                        'EntityId' => (int) $campaign->getExternalCampaignId(),
                        'EntityType' => 'Campaign',
                        'NegativeKeywords' => [
                            'NegativeKeyword' => [[
                                'Id' => (int) $keyword->getExternalKeywordId(),
                            ]],
                        ],
                    ]],
                ],
            ]]);

            $this->throwOnNestedPartialErrors($response->NestedPartialErrors ?? null, 'Microsoft negative keyword removal failed.');
            return;
        }

        if (!$adGroup instanceof AdsAdGroup) {
            throw new \RuntimeException('Positive keyword requires an ad group.');
        }

        $response = $client->__soapCall('DeleteKeywords', [[
            'AdGroupId' => (int) $adGroup->getExternalAdgroupId(),
            'KeywordIds' => ['long' => [(int) $keyword->getExternalKeywordId()]],
        ]]);

        $this->throwOnPartialErrors($response->PartialErrors ?? null, 'Microsoft keyword removal failed.');
    }

    public function syncMetrics(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return [];
    }

    /**
     * @return array<int, array{account: string, id: string, name: string, status: string}>
     */
    public function fetchConversionGoals(AdsAccount $account): array
    {
        $client = $this->createCampaignClient($account);
        $goals = [];

        try {
            $response = $client->__soapCall('GetConversionGoalsByIds', [[
                'ConversionGoalIds' => null,
                'ConversionGoalTypes' => [
                    'OfflineConversion'
                ],
            ]]);
        } catch (\Throwable $e) {
            $this->logger->error('MicrosoftAdsProvider::fetchConversionGoals error: ' . $e->getMessage());
            return [];
        }

        $conversionGoals = $this->normalizeList($response->ConversionGoals->ConversionGoal ?? []);
        foreach ($conversionGoals as $goal) {
            $id = (string) ($goal->Id ?? '');
            if ('' === $id) {
                continue;
            }

            $goals[] = [
                'account' => $account->getName(),
                'id'      => $id,
                'name'    => (string) ($goal->Name ?? 'Goal ' . $id),
                'status'  => strtolower((string) ($goal->Status ?? '')),
            ];
        }

        return $goals;
    }

    /**
     * Creates an OfflineConversionGoal in Microsoft Advertising.
     *
     * @return array{id: string, name: string}
     */
    public function createOfflineConversionGoal(AdsAccount $account, string $name): array
    {
        $name = trim($name);
        if ('' === $name) {
            throw new \RuntimeException('Conversion goal name is required.');
        }

        $client = $this->createCampaignClient($account);

        $offlineConversionGoal = [
            'GoalCategory' => 'Purchase',
            'ConversionWindowInMinutes' => 43200, // 30 days
            'CountType' => 'All',
            'Name' => $name,
            'Revenue' => [
                'Type' => 'FixedValue',
                'Value' => 0.0,
                'CurrencyCode' => null,
            ],
            'Scope' => 'Account',
            'Status' => 'Active',
            'Type' => 'OfflineConversion',
        ];

        try {
            $response = $client->__soapCall('AddConversionGoals', [[
                'ConversionGoals' => [
                    'ConversionGoal' => [
                        new \SoapVar($offlineConversionGoal, SOAP_ENC_OBJECT, 'OfflineConversionGoal', self::CAMPAIGN_NS),
                    ],
                ],
            ]]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Microsoft Advertising conversion goal creation failed: '.$e->getMessage(), 0, $e);
        }

        $errors = [];
        foreach ($this->normalizeList($response->PartialErrors->BatchError ?? []) as $error) {
            $message = trim(sprintf('%s %s', (string) ($error->Code ?? ''), (string) ($error->Message ?? '')));
            if ('' !== $message) {
                $errors[] = $message;
            }
        }

        if ([] !== $errors) {
            throw new \RuntimeException('Microsoft Advertising partial error: '.implode('; ', $errors));
        }

        $ids = $this->normalizeList($response->ConversionGoalIds->long ?? []);
        $id = (string) ($ids[0] ?? '');

        if ('' === $id) {
            throw new \RuntimeException('Microsoft Advertising did not return the created conversion goal ID.');
        }

        return ['id' => $id, 'name' => $name];
    }

    public function uploadOfflineConversions(AdsAccount $account, array $conversions): array
    {
        $client = $this->createCampaignClient($account);
        $offlineConversions = [];
        $errors = [];

        foreach ($conversions as $conversion) {
            $clickId = (string) ($conversion['click_id'] ?? '');
            $clickIdType = strtolower((string) ($conversion['click_id_type'] ?? 'msclkid'));
            $hashedEmail = (string) ($conversion['hashed_email'] ?? '');
            $hashedPhone = (string) ($conversion['hashed_phone'] ?? '');

            if ('' === $clickId && '' === $hashedEmail && '' === $hashedPhone) {
                $errors[] = 'Microsoft conversion skipped: missing msclkid or enhanced conversion identifier.';
                continue;
            }

            $offlineConversion = [
                'ConversionCurrencyCode' => (string) ($conversion['conversion_currency'] ?? 'BRL'),
                'ConversionName'         => (string) ($conversion['conversion_name'] ?? ''),
                'ConversionTime'         => $this->formatMicrosoftConversionTime((string) $conversion['conversion_time']),
                'ConversionValue'        => (float) ($conversion['conversion_value'] ?? 0),
            ];

            if ('' !== $clickId && in_array($clickIdType, ['msclkid', 'microsoftclickid'], true)) {
                $offlineConversion['MicrosoftClickId'] = $clickId;
            }
            if ('' !== $hashedEmail) {
                $offlineConversion['HashedEmailAddress'] = $hashedEmail;
            }
            if ('' !== $hashedPhone) {
                $offlineConversion['HashedPhoneNumber'] = $hashedPhone;
            }

            $offlineConversions[] = $offlineConversion;
        }

        if ([] === $offlineConversions) {
            return ['success' => false, 'uploaded' => 0, 'failed' => count($conversions), 'errors' => $errors ?: ['No valid Microsoft conversions to upload.']];
        }

        try {
            $response = $client->__soapCall('ApplyOfflineConversions', [[
                'OfflineConversions' => [
                    'OfflineConversion' => $offlineConversions,
                ],
            ]]);
        } catch (\Throwable $e) {
            return ['success' => false, 'uploaded' => 0, 'failed' => count($conversions), 'errors' => [$e->getMessage()]];
        }

        foreach ($this->normalizeList($response->PartialErrors->BatchError ?? []) as $error) {
            $message = trim(sprintf('%s %s', (string) ($error->Code ?? ''), (string) ($error->Message ?? '')));
            $errors[] = '' !== $message ? $message : 'Microsoft partial error uploading offline conversion.';
        }

        $failed = count($errors);
        $uploaded = max(0, count($offlineConversions) - $failed);

        return ['success' => 0 === $failed, 'uploaded' => $uploaded, 'failed' => $failed, 'errors' => $errors];
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
            $this->logger->error('AdsIntelligence: unable to refresh Microsoft Advertising token: '.($refresh['message'] ?? 'unknown error'));

            return null;
        }

        return $refresh['data']['access_token'] ?? null;
    }

    private function createCampaignClient(AdsAccount $account): \SoapClient
    {
        $credential = $this->entityManager->getRepository(AdsProviderCredential::class)->find((int) $account->getCredentialId());
        if (!$credential instanceof AdsProviderCredential) {
            throw new \RuntimeException(sprintf('Credential #%d not found for account %s.', $account->getCredentialId(), $account->getExternalAccountId()));
        }

        $accessToken = $this->getValidAccessToken($credential);
        if (null === $accessToken) {
            throw new \RuntimeException('Unable to obtain a valid Microsoft Advertising access token.');
        }

        $developerToken = $this->getDeveloperToken();
        if ('' === $developerToken) {
            throw new \RuntimeException('Microsoft Advertising developer token is not configured.');
        }

        return $this->createSoapClient(
            self::CAMPAIGN_WSDL,
            self::CAMPAIGN_NS,
            $developerToken,
            $accessToken,
            (string) $account->getExternalManagerId(),
            $account->getExternalAccountId(),
        );
    }

    private function formatMicrosoftConversionTime(string $value): string
    {
        return (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    private function normalizeMatchType(string $matchType): string
    {
        return 'Phrase';
    }

    private function normalizeNegativeMatchType(string $matchType): string
    {
        return 'Phrase';
    }

    private function firstLong(mixed $value): ?int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function throwOnPartialErrors(mixed $partialErrors, string $fallback): void
    {
        foreach ($this->normalizeList($partialErrors->BatchError ?? $partialErrors ?? []) as $error) {
            $message = (string) ($error->Message ?? $fallback);
            if ('' !== $message) {
                throw new \RuntimeException($message);
            }
        }
    }

    private function throwOnNestedPartialErrors(mixed $nestedPartialErrors, string $fallback): void
    {
        foreach ($this->normalizeList($nestedPartialErrors->BatchErrorCollection ?? $nestedPartialErrors ?? []) as $collection) {
            $this->throwOnPartialErrors($collection->BatchErrors ?? $collection, $fallback);
        }
    }

    private function getDeveloperToken(): string
    {
        $keys = $this->getIntegrationApiKeys();

        return (string) ($keys['developer_token'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function getIntegrationApiKeys(): array
    {
        $integration = $this->entityManager->getRepository(Integration::class)->findOneBy([
            'name' => 'AdsIntelligenceMicrosoft',
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

    private function encryptNullable(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return $this->encryptionHelper->encrypt((string) $value);
    }

    private function getExpiresAt(mixed $expiresAt): ?\DateTimeInterface
    {
        if (empty($expiresAt)) {
            return null;
        }

        if (is_numeric($expiresAt)) {
            return (new \DateTime())->setTimestamp((int) $expiresAt);
        }

        try {
            return new \DateTime((string) $expiresAt);
        } catch (\Throwable) {
            return null;
        }
    }

    private function createSoapClient(
        string $wsdl,
        string $namespace,
        string $developerToken,
        string $accessToken,
        ?string $customerId = null,
        ?string $accountId = null,
    ): \SoapClient {
        $client = new \SoapClient($wsdl, [
            'trace'      => false,
            'exceptions' => true,
            'features'   => SOAP_SINGLE_ELEMENT_ARRAYS,
        ]);

        $headers = [
            new \SoapHeader($namespace, 'DeveloperToken', $developerToken),
            new \SoapHeader($namespace, 'AuthenticationToken', $accessToken),
        ];

        if (null !== $customerId && '' !== $customerId) {
            $headers[] = new \SoapHeader($namespace, 'CustomerId', $customerId);
        }

        if (null !== $accountId && '' !== $accountId) {
            $headers[] = new \SoapHeader($namespace, 'CustomerAccountId', $accountId);
        }

        $client->__setSoapHeaders($headers);

        return $client;
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeList(mixed $value): array
    {
        if (null === $value) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * @return array<int, mixed>
     */
    private function soapList(mixed $container, string $itemProperty): array
    {
        if (null === $container) {
            return [];
        }

        if (is_object($container) && isset($container->{$itemProperty})) {
            return $this->normalizeList($container->{$itemProperty});
        }

        return $this->normalizeList($container);
    }
}
