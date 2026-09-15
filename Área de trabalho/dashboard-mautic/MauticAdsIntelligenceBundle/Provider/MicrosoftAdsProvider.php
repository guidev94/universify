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

    private const REPORTING_WSDL = 'https://reporting.api.bingads.microsoft.com/Api/Advertiser/Reporting/v13/ReportingService.svc?singleWsdl';
    private const REPORTING_NS = 'https://bingads.microsoft.com/Reporting/v13';

    /**
     * Reporting is asynchronous: submit, then poll until the report is generated.
     * Bounded so a sync run can never hang indefinitely.
     */
    private const REPORT_POLL_MAX_ATTEMPTS = 30;
    private const REPORT_POLL_INTERVAL_SECONDS = 5;

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

    /**
     * Pulls daily campaign performance from the Bing Ads Reporting API.
     *
     * Reporting is asynchronous: submit a CampaignPerformanceReportRequest, poll until
     * it is generated, download the (zipped) CSV and parse it. Returns rows in the same
     * normalized shape as the other providers so SyncMetricsCommand can upsert them.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syncMetrics(AdsAccount $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException(
                'The PHP zip extension is required to download Microsoft Advertising reports'
                .' (they are delivered as ZIP archives) but is not enabled on this server.'
                .' Install/enable it (e.g. "apt install php-zip" or "docker-php-ext-install zip")'
                .' and restart PHP-FPM.'
            );
        }

        $client = $this->createReportingClient($account);

        $reportRequestId = $this->submitCampaignPerformanceReport($client, $account, $from, $to);
        if ('' === $reportRequestId) {
            $this->logger->warning(sprintf(
                'AdsIntelligence: Microsoft report submission for account %s returned no ReportRequestId.',
                $account->getExternalAccountId()
            ));

            return [];
        }

        $downloadUrl = $this->pollReportDownloadUrl($client, $reportRequestId, $account->getExternalAccountId());
        if (null === $downloadUrl) {
            // Success with no URL means there is simply no data for the requested window.
            return [];
        }

        $csv = $this->downloadAndExtractReportCsv($downloadUrl);
        if (null === $csv) {
            return [];
        }

        return $this->parseCampaignPerformanceCsv($csv);
    }

    /**
     * Submits the report request and returns the ReportRequestId.
     */
    private function submitCampaignPerformanceReport(
        \SoapClient $client,
        AdsAccount $account,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): string {
        $accountId = (int) preg_replace('/\D+/', '', $account->getExternalAccountId());
        if (0 === $accountId) {
            throw new \RuntimeException(sprintf('Invalid Microsoft account id "%s".', $account->getExternalAccountId()));
        }

        $reportRequest = new \stdClass();
        $reportRequest->Format                 = 'Csv';
        $reportRequest->FormatVersion          = '2.0';
        $reportRequest->ReportName             = 'Mautic AdsIntelligence Campaign Performance';
        $reportRequest->ReturnOnlyCompleteData  = false;
        // Keep column headers so columns can be mapped by name instead of by position.
        $reportRequest->ExcludeColumnHeaders   = false;
        $reportRequest->ExcludeReportHeader    = true;
        $reportRequest->ExcludeReportFooter    = true;
        $reportRequest->Aggregation            = 'Daily';

        // TimePeriod is required for every aggregation other than Summary.
        $reportRequest->Columns = [
            'CampaignPerformanceReportColumn' => [
                'TimePeriod',
                'CampaignId',
                'CampaignName',
                'Impressions',
                'Clicks',
                'Spend',
                'Conversions',
                'Revenue',
            ],
        ];

        $reportRequest->Scope = [
            'AccountIds' => ['long' => [$accountId]],
        ];

        $reportRequest->Time = [
            'CustomDateRangeStart' => [
                'Day'   => (int) $from->format('j'),
                'Month' => (int) $from->format('n'),
                'Year'  => (int) $from->format('Y'),
            ],
            'CustomDateRangeEnd' => [
                'Day'   => (int) $to->format('j'),
                'Month' => (int) $to->format('n'),
                'Year'  => (int) $to->format('Y'),
            ],
        ];

        // ReportRequest is polymorphic; the concrete type must be declared via xsi:type.
        $typedRequest = new \SoapVar(
            $reportRequest,
            SOAP_ENC_OBJECT,
            'CampaignPerformanceReportRequest',
            self::REPORTING_NS
        );

        try {
            $response = $client->__soapCall('SubmitGenerateReport', [[
                'ReportRequest' => $typedRequest,
            ]]);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'AdsIntelligence: Microsoft SubmitGenerateReport failed for account %s: %s',
                $account->getExternalAccountId(),
                $e->getMessage()
            ));

            throw new \RuntimeException('Microsoft SubmitGenerateReport failed: '.$e->getMessage(), 0, $e);
        }

        return (string) ($response->ReportRequestId ?? '');
    }

    /**
     * Polls until the report is generated. Returns the download URL, or null when the
     * report succeeded but holds no data.
     */
    private function pollReportDownloadUrl(\SoapClient $client, string $reportRequestId, string $accountId): ?string
    {
        for ($attempt = 1; $attempt <= self::REPORT_POLL_MAX_ATTEMPTS; ++$attempt) {
            try {
                $response = $client->__soapCall('PollGenerateReport', [[
                    'ReportRequestId' => $reportRequestId,
                ]]);
            } catch (\Throwable $e) {
                throw new \RuntimeException('Microsoft PollGenerateReport failed: '.$e->getMessage(), 0, $e);
            }

            $status = $response->ReportRequestStatus ?? null;
            $state  = (string) ($status->Status ?? '');

            if ('Error' === $state) {
                throw new \RuntimeException(sprintf(
                    'Microsoft report generation failed for account %s (request %s).',
                    $accountId,
                    $reportRequestId
                ));
            }

            if ('Success' === $state) {
                $url = (string) ($status->ReportDownloadUrl ?? '');

                if ('' === $url) {
                    $this->logger->info(sprintf(
                        'AdsIntelligence: Microsoft report for account %s has no data in the requested period.',
                        $accountId
                    ));

                    return null;
                }

                return $url;
            }

            // Still Pending — wait before polling again.
            if ($attempt < self::REPORT_POLL_MAX_ATTEMPTS) {
                sleep(self::REPORT_POLL_INTERVAL_SECONDS);
            }
        }

        throw new \RuntimeException(sprintf(
            'Microsoft report for account %s did not finish within %d seconds.',
            $accountId,
            self::REPORT_POLL_MAX_ATTEMPTS * self::REPORT_POLL_INTERVAL_SECONDS
        ));
    }

    /**
     * Downloads the zipped report and returns the contents of the CSV inside it.
     */
    private function downloadAndExtractReportCsv(string $downloadUrl): ?string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'ads_ms_report_');
        if (false === $zipPath) {
            throw new \RuntimeException('Unable to create a temporary file for the Microsoft report download.');
        }

        try {
            $response = $this->httpClient->request('GET', $downloadUrl, ['timeout' => 120]);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException(sprintf(
                    'Microsoft report download returned HTTP %d.',
                    $response->getStatusCode()
                ));
            }

            if (false === file_put_contents($zipPath, $response->getContent())) {
                throw new \RuntimeException('Unable to write the Microsoft report download to disk.');
            }

            $zip = new \ZipArchive();
            if (true !== $zip->open($zipPath)) {
                throw new \RuntimeException('Unable to open the Microsoft report archive.');
            }

            $csv = null;
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = (string) $zip->getNameIndex($i);
                if ('' === $name || str_ends_with($name, '/')) {
                    continue;
                }

                $contents = $zip->getFromIndex($i);
                if (false !== $contents) {
                    $csv = $contents;
                    break;
                }
            }

            $zip->close();

            if (null === $csv || '' === trim($csv)) {
                return null;
            }

            return $csv;
        } finally {
            if (is_file($zipPath)) {
                unlink($zipPath);
            }
        }
    }

    /**
     * Parses the campaign performance CSV into normalized metric rows.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseCampaignPerformanceCsv(string $csv): array
    {
        // Microsoft prefixes the CSV with a UTF-8 BOM, which would otherwise become
        // part of the first header cell and break column lookups.
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;

        $handle = fopen('php://temp', 'r+');
        if (false === $handle) {
            throw new \RuntimeException('Unable to open a stream to parse the Microsoft report.');
        }

        fwrite($handle, $csv);
        rewind($handle);

        $header = null;
        $metrics = [];

        while (false !== ($row = fgetcsv($handle))) {
            if (null === $row || [null] === $row) {
                continue;
            }

            // Skip blank lines the report may contain around the data block.
            $nonEmpty = array_filter($row, static fn ($v): bool => null !== $v && '' !== trim((string) $v));
            if ([] === $nonEmpty) {
                continue;
            }

            if (null === $header) {
                $header = array_map(
                    static fn ($v): string => trim((string) $v, " \t\n\r\0\x0B\xEF\xBB\xBF\""),
                    $row
                );
                continue;
            }

            $assoc = [];
            foreach ($header as $index => $column) {
                $assoc[$column] = $row[$index] ?? null;
            }

            $date = $this->normalizeReportDate((string) ($assoc['TimePeriod'] ?? ''));
            if (null === $date) {
                continue;
            }

            $metrics[] = [
                'date'               => $date,
                'externalCampaignId' => trim((string) ($assoc['CampaignId'] ?? '')),
                'impressions'        => (int) $this->numericFromReport($assoc['Impressions'] ?? null),
                'clicks'             => (int) $this->numericFromReport($assoc['Clicks'] ?? null),
                'cost'               => $this->numericFromReport($assoc['Spend'] ?? null),
                'conversions'        => $this->numericFromReport($assoc['Conversions'] ?? null),
                'conversionValue'    => $this->numericFromReport($assoc['Revenue'] ?? null),
            ];
        }

        fclose($handle);

        return $metrics;
    }

    /**
     * Report dates arrive in a few shapes depending on FormatVersion and locale.
     * Returns Y-m-d, or null when the value is not a date (footer/summary rows).
     */
    private function normalizeReportDate(string $value): ?string
    {
        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        if (1 === preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return $value;
        }

        if (1 === preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[1], (int) $m[2]);
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Report cells can carry thousands separators, currency symbols, percentages or
     * "--" when a value was not computed. Anything non-numeric becomes 0.0.
     */
    private function numericFromReport(mixed $value): float
    {
        if (null === $value) {
            return 0.0;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        if (null === $clean || '' === $clean || '-' === $clean) {
            return 0.0;
        }

        return (float) $clean;
    }

    private function createReportingClient(AdsAccount $account): \SoapClient
    {
        $credential = $this->entityManager->getRepository(AdsProviderCredential::class)->find((int) $account->getCredentialId());
        if (!$credential instanceof AdsProviderCredential) {
            throw new \RuntimeException(sprintf(
                'Credential #%d not found for account %s.',
                $account->getCredentialId(),
                $account->getExternalAccountId()
            ));
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
            self::REPORTING_WSDL,
            self::REPORTING_NS,
            $developerToken,
            $accessToken,
            (string) $account->getExternalManagerId(),
            $account->getExternalAccountId(),
        );
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

            if (!str_contains($value, '|')) {
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

        // Values not produced by EncryptionHelper have no "<payload>|<iv>" shape.
        if (!str_contains($encrypted, '|')) {
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
        // Every Microsoft Advertising call goes through here, so this is the one place
        // that needs to check the extension. Without it PHP raises a bare
        // "Class SoapClient not found", which says nothing about how to fix it.
        if (!class_exists(\SoapClient::class)) {
            throw new \RuntimeException(
                'The PHP soap extension is required by Microsoft Advertising (Bing Ads uses a SOAP API)'
                .' but is not enabled on this server. Install/enable it (e.g. "apt install php-soap"'
                .' or "docker-php-ext-install soap") and restart PHP-FPM.'
            );
        }

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
