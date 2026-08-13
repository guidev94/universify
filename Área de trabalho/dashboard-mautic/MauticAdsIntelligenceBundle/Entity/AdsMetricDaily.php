<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsMetricDailyRepository;

class AdsMetricDaily
{
    private ?int $id = null;

    private string $provider = '';

    private int $accountId = 0;

    private ?int $campaignId = null;

    private ?int $adgroupId = null;

    private ?int $adId = null;

    private ?int $keywordId = null;

    private \DateTimeInterface $metricDate;

    private int $impressions = 0;

    private int $clicks = 0;

    private float $cost = 0.0;

    private float $conversions = 0.0;

    private float $conversionValue = 0.0;

    private int $leads = 0;

    private int $coldLeads = 0;

    private int $warmLeads = 0;

    private int $hotLeads = 0;

    private int $opportunities = 0;

    private int $customers = 0;

    private int $lostLeads = 0;

    /** @var array<string, mixed>|null */
    private ?array $lostReasons = null;

    private float $revenue = 0.0;

    private \DateTimeInterface $createdAt;

    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt  = new \DateTime();
        $this->metricDate = new \DateTime('today');
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_metrics_daily')
            ->setCustomRepositoryClass(AdsMetricDailyRepository::class)
            ->addIndex(['provider', 'metric_date'], 'idx_provider_date')
            ->addIndex(['campaign_id', 'metric_date'], 'idx_campaign_date');

        $builder->addId();

        $builder->addNamedField('provider', Types::STRING, 'provider');

        $builder->createField('accountId', Types::INTEGER)
            ->columnName('account_id')
            ->build();

        $builder->createField('campaignId', Types::INTEGER)
            ->columnName('campaign_id')
            ->nullable()
            ->build();

        $builder->createField('adgroupId', Types::INTEGER)
            ->columnName('adgroup_id')
            ->nullable()
            ->build();

        $builder->createField('adId', Types::INTEGER)
            ->columnName('ad_id')
            ->nullable()
            ->build();

        $builder->createField('keywordId', Types::INTEGER)
            ->columnName('keyword_id')
            ->nullable()
            ->build();

        $builder->createField('metricDate', Types::DATE_MUTABLE)
            ->columnName('metric_date')
            ->build();

        $builder->addNamedField('impressions', Types::INTEGER, 'impressions');
        $builder->addNamedField('clicks', Types::INTEGER, 'clicks');

        $builder->createField('cost', Types::DECIMAL)
            ->columnName('cost')
            ->precision(14)
            ->scale(4)
            ->build();

        $builder->createField('conversions', Types::DECIMAL)
            ->columnName('conversions')
            ->precision(14)
            ->scale(4)
            ->build();

        $builder->createField('conversionValue', Types::DECIMAL)
            ->columnName('conversion_value')
            ->precision(14)
            ->scale(4)
            ->build();

        $builder->addNamedField('leads', Types::INTEGER, 'leads');

        $builder->createField('coldLeads', Types::INTEGER)
            ->columnName('cold_leads')
            ->build();

        $builder->createField('warmLeads', Types::INTEGER)
            ->columnName('warm_leads')
            ->build();

        $builder->createField('hotLeads', Types::INTEGER)
            ->columnName('hot_leads')
            ->build();

        $builder->addNamedField('opportunities', Types::INTEGER, 'opportunities');
        $builder->addNamedField('customers', Types::INTEGER, 'customers');

        $builder->createField('lostLeads', Types::INTEGER)
            ->columnName('lost_leads')
            ->build();

        $builder->createField('lostReasons', Types::JSON)
            ->columnName('lost_reasons')
            ->nullable()
            ->build();

        $builder->createField('revenue', Types::DECIMAL)
            ->columnName('revenue')
            ->precision(14)
            ->scale(2)
            ->build();

        $builder->addNamedField('createdAt', Types::DATETIME_MUTABLE, 'created_at');

        $builder->createField('updatedAt', Types::DATETIME_MUTABLE)
            ->columnName('updated_at')
            ->nullable()
            ->build();
    }

    public function getId(): ?int { return $this->id; }
    public function getProvider(): string { return $this->provider; }
    public function setProvider(string $provider): self { $this->provider = $provider; return $this; }
    public function getAccountId(): int { return $this->accountId; }
    public function setAccountId(int $accountId): self { $this->accountId = $accountId; return $this; }
    public function getCampaignId(): ?int { return $this->campaignId; }
    public function setCampaignId(?int $campaignId): self { $this->campaignId = $campaignId; return $this; }
    public function getAdgroupId(): ?int { return $this->adgroupId; }
    public function setAdgroupId(?int $adgroupId): self { $this->adgroupId = $adgroupId; return $this; }
    public function getAdId(): ?int { return $this->adId; }
    public function setAdId(?int $adId): self { $this->adId = $adId; return $this; }
    public function getKeywordId(): ?int { return $this->keywordId; }
    public function setKeywordId(?int $keywordId): self { $this->keywordId = $keywordId; return $this; }
    public function getMetricDate(): \DateTimeInterface { return $this->metricDate; }
    public function setMetricDate(\DateTimeInterface $metricDate): self { $this->metricDate = $metricDate; return $this; }
    public function getImpressions(): int { return $this->impressions; }
    public function setImpressions(int $impressions): self { $this->impressions = $impressions; return $this; }
    public function getClicks(): int { return $this->clicks; }
    public function setClicks(int $clicks): self { $this->clicks = $clicks; return $this; }
    public function getCost(): float { return $this->cost; }
    public function setCost(float $cost): self { $this->cost = $cost; return $this; }
    public function getConversions(): float { return $this->conversions; }
    public function setConversions(float $conversions): self { $this->conversions = $conversions; return $this; }
    public function getConversionValue(): float { return $this->conversionValue; }
    public function setConversionValue(float $conversionValue): self { $this->conversionValue = $conversionValue; return $this; }
    public function getLeads(): int { return $this->leads; }
    public function setLeads(int $leads): self { $this->leads = $leads; return $this; }
    public function getColdLeads(): int { return $this->coldLeads; }
    public function setColdLeads(int $coldLeads): self { $this->coldLeads = $coldLeads; return $this; }
    public function getWarmLeads(): int { return $this->warmLeads; }
    public function setWarmLeads(int $warmLeads): self { $this->warmLeads = $warmLeads; return $this; }
    public function getHotLeads(): int { return $this->hotLeads; }
    public function setHotLeads(int $hotLeads): self { $this->hotLeads = $hotLeads; return $this; }
    public function getOpportunities(): int { return $this->opportunities; }
    public function setOpportunities(int $opportunities): self { $this->opportunities = $opportunities; return $this; }
    public function getCustomers(): int { return $this->customers; }
    public function setCustomers(int $customers): self { $this->customers = $customers; return $this; }
    public function getLostLeads(): int { return $this->lostLeads; }
    public function setLostLeads(int $lostLeads): self { $this->lostLeads = $lostLeads; return $this; }
    public function getLostReasons(): ?array { return $this->lostReasons; }
    public function setLostReasons(?array $lostReasons): self { $this->lostReasons = $lostReasons; return $this; }
    public function getRevenue(): float { return $this->revenue; }
    public function setRevenue(float $revenue): self { $this->revenue = $revenue; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
