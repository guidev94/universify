<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Service\Intelligence;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\GoogleAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\LinkedInAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\MicrosoftAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence\PerformanceDataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PerformanceDataServiceTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|GoogleAdsProvider $googleAdsProvider;
    private MockObject|MicrosoftAdsProvider $microsoftAdsProvider;
    private MockObject|LinkedInAdsProvider $linkedInAdsProvider;
    private PerformanceDataService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->googleAdsProvider = $this->createMock(GoogleAdsProvider::class);
        $this->microsoftAdsProvider = $this->createMock(MicrosoftAdsProvider::class);
        $this->linkedInAdsProvider = $this->createMock(LinkedInAdsProvider::class);

        $this->service = new PerformanceDataService(
            $this->entityManager,
            $this->googleAdsProvider,
            $this->microsoftAdsProvider,
            $this->linkedInAdsProvider
        );
    }

    public function testGetClickPerformanceDataReturnsMockWhenNoAccounts(): void
    {
        $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repository->method('findBy')->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $data = $this->service->getClickPerformanceData([]);

        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('tableData', $data);
        $this->assertGreaterThan(0, count($data['labels']));
    }

    public function testGetClickPerformanceDataAggregatesMultipleProviders(): void
    {
        $account1 = $this->createMock(AdsAccount::class);
        $account1->method('getProvider')->willReturn('google');
        $account2 = $this->createMock(AdsAccount::class);
        $account2->method('getProvider')->willReturn('microsoft');

        $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repository->method('findBy')->willReturn([$account1, $account2]);

        $this->entityManager->method('getRepository')->willReturn($repository);

        $this->googleAdsProvider->expects($this->once())
            ->method('fetchClickPerformance')
            ->willReturn([
                ['segments' => ['date' => '2026-08-01'], 'campaign' => ['name' => 'G1'], 'metrics' => ['clicks' => 10, 'impressions' => 100, 'costMicros' => 1000000]]
            ]);

        $this->microsoftAdsProvider->expects($this->once())
            ->method('syncMetrics')
            ->willReturn([
                ['segments' => ['date' => '2026-08-01'], 'campaign' => ['name' => 'M1'], 'metrics' => ['clicks' => 5, 'impressions' => 50, 'costMicros' => 500000]]
            ]);

        $data = $this->service->getClickPerformanceData([]);

        $this->assertCount(2, $data['tableData']);
        $this->assertEquals('G1', $data['tableData'][0]['campaign']);
        $this->assertEquals('M1', $data['tableData'][1]['campaign']);
    }
}
