<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Service\Intelligence;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
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
    private MockObject|Connection $connection;
    private PerformanceDataService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->googleAdsProvider = $this->createMock(GoogleAdsProvider::class);
        $this->microsoftAdsProvider = $this->createMock(MicrosoftAdsProvider::class);
        $this->linkedInAdsProvider = $this->createMock(LinkedInAdsProvider::class);
        $this->connection = $this->createMock(Connection::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);

        $this->service = new PerformanceDataService(
            $this->entityManager,
            $this->googleAdsProvider,
            $this->microsoftAdsProvider,
            $this->linkedInAdsProvider
        );
    }

    public function testGetClickPerformanceDataQueriesDatabase(): void
    {
        $filters = ['account_id' => 1];
        
        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['date' => '2026-08-01', 'campaign' => 'C1', 'clicks' => 10, 'impressions' => 100, 'conversions' => 1, 'cost' => 50.0]
            ]);

        $data = $this->service->getClickPerformanceData($filters);

        $this->assertArrayHasKey('tableData', $data);
        $this->assertCount(1, $data['tableData']);
        $this->assertEquals('C1', $data['tableData'][0]['campaign']);
    }

    public function testGetKeywordPerformanceDataQueriesDatabase(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['keyword' => 'kw1', 'qs' => 8, 'match_type' => 'broad', 'status' => 'enabled', 'campaign' => 'C1', 'clicks' => 50, 'impressions' => 500, 'cost' => 200.0]
            ]);

        $data = $this->service->getKeywordPerformanceData([]);

        $this->assertArrayHasKey('tableData', $data);
        $this->assertCount(1, $data['tableData']);
        $this->assertEquals('kw1', $data['tableData'][0]['keyword']);
        $this->assertGreaterThan(0, $data['tableData'][0]['heatmap_intensity']);
    }
}
