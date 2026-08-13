<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsLeadAttributionRepository;

class AdsLeadAttribution
{
    private ?int $id = null;

    private int $leadId = 0;

    private ?string $provider = null;

    private ?string $source = null;

    private ?string $medium = null;

    private ?int $accountId = null;

    private ?int $campaignId = null;

    private ?int $adgroupId = null;

    private ?int $adId = null;

    private ?int $keywordId = null;

    private ?string $externalCampaignId = null;

    private ?string $externalAdgroupId = null;

    private ?string $externalAdId = null;

    private ?string $externalKeywordId = null;

    private ?string $clickId = null;

    private ?string $clickIdType = null;

    private ?string $utmSource = null;

    private ?string $utmMedium = null;

    private ?string $utmCampaign = null;

    private ?string $utmContent = null;

    private ?string $utmTerm = null;

    private ?\DateTimeInterface $firstTouchAt = null;

    private ?\DateTimeInterface $lastTouchAt = null;

    private \DateTimeInterface $createdAt;

    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_lead_attribution')
            ->setCustomRepositoryClass(AdsLeadAttributionRepository::class)
            ->addIndex(['lead_id'], 'idx_lead_id')
            ->addIndex(['provider', 'click_id'], 'idx_provider_click')
            ->addIndex(['campaign_id'], 'idx_campaign_id');

        $builder->addId();

        $builder->createField('leadId', Types::INTEGER)
            ->columnName('lead_id')
            ->build();

        $builder->addNamedField('provider', Types::STRING, 'provider', true);
        $builder->addNamedField('source', Types::STRING, 'source', true);
        $builder->addNamedField('medium', Types::STRING, 'medium', true);

        $builder->createField('accountId', Types::INTEGER)
            ->columnName('account_id')
            ->nullable()
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

        $builder->createField('externalCampaignId', Types::STRING)
            ->columnName('external_campaign_id')
            ->nullable()
            ->build();

        $builder->createField('externalAdgroupId', Types::STRING)
            ->columnName('external_adgroup_id')
            ->nullable()
            ->build();

        $builder->createField('externalAdId', Types::STRING)
            ->columnName('external_ad_id')
            ->nullable()
            ->build();

        $builder->createField('externalKeywordId', Types::STRING)
            ->columnName('external_keyword_id')
            ->nullable()
            ->build();

        $builder->createField('clickId', Types::STRING)
            ->columnName('click_id')
            ->nullable()
            ->build();

        $builder->createField('clickIdType', Types::STRING)
            ->columnName('click_id_type')
            ->nullable()
            ->build();

        $builder->createField('utmSource', Types::STRING)
            ->columnName('utm_source')
            ->nullable()
            ->build();

        $builder->createField('utmMedium', Types::STRING)
            ->columnName('utm_medium')
            ->nullable()
            ->build();

        $builder->createField('utmCampaign', Types::STRING)
            ->columnName('utm_campaign')
            ->nullable()
            ->build();

        $builder->createField('utmContent', Types::STRING)
            ->columnName('utm_content')
            ->nullable()
            ->build();

        $builder->createField('utmTerm', Types::STRING)
            ->columnName('utm_term')
            ->nullable()
            ->build();

        $builder->createField('firstTouchAt', Types::DATETIME_MUTABLE)
            ->columnName('first_touch_at')
            ->nullable()
            ->build();

        $builder->createField('lastTouchAt', Types::DATETIME_MUTABLE)
            ->columnName('last_touch_at')
            ->nullable()
            ->build();

        $builder->addNamedField('createdAt', Types::DATETIME_MUTABLE, 'created_at');

        $builder->createField('updatedAt', Types::DATETIME_MUTABLE)
            ->columnName('updated_at')
            ->nullable()
            ->build();
    }

    public function getId(): ?int { return $this->id; }
    public function getLeadId(): int { return $this->leadId; }
    public function setLeadId(int $leadId): self { $this->leadId = $leadId; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $provider): self { $this->provider = $provider; return $this; }
    public function getSource(): ?string { return $this->source; }
    public function setSource(?string $source): self { $this->source = $source; return $this; }
    public function getMedium(): ?string { return $this->medium; }
    public function setMedium(?string $medium): self { $this->medium = $medium; return $this; }
    public function getAccountId(): ?int { return $this->accountId; }
    public function setAccountId(?int $accountId): self { $this->accountId = $accountId; return $this; }
    public function getCampaignId(): ?int { return $this->campaignId; }
    public function setCampaignId(?int $campaignId): self { $this->campaignId = $campaignId; return $this; }
    public function getAdgroupId(): ?int { return $this->adgroupId; }
    public function setAdgroupId(?int $adgroupId): self { $this->adgroupId = $adgroupId; return $this; }
    public function getAdId(): ?int { return $this->adId; }
    public function setAdId(?int $adId): self { $this->adId = $adId; return $this; }
    public function getKeywordId(): ?int { return $this->keywordId; }
    public function setKeywordId(?int $keywordId): self { $this->keywordId = $keywordId; return $this; }
    public function getExternalCampaignId(): ?string { return $this->externalCampaignId; }
    public function setExternalCampaignId(?string $v): self { $this->externalCampaignId = $v; return $this; }
    public function getExternalAdgroupId(): ?string { return $this->externalAdgroupId; }
    public function setExternalAdgroupId(?string $v): self { $this->externalAdgroupId = $v; return $this; }
    public function getExternalAdId(): ?string { return $this->externalAdId; }
    public function setExternalAdId(?string $v): self { $this->externalAdId = $v; return $this; }
    public function getExternalKeywordId(): ?string { return $this->externalKeywordId; }
    public function setExternalKeywordId(?string $v): self { $this->externalKeywordId = $v; return $this; }
    public function getClickId(): ?string { return $this->clickId; }
    public function setClickId(?string $clickId): self { $this->clickId = $clickId; return $this; }
    public function getClickIdType(): ?string { return $this->clickIdType; }
    public function setClickIdType(?string $clickIdType): self { $this->clickIdType = $clickIdType; return $this; }
    public function getUtmSource(): ?string { return $this->utmSource; }
    public function setUtmSource(?string $utmSource): self { $this->utmSource = $utmSource; return $this; }
    public function getUtmMedium(): ?string { return $this->utmMedium; }
    public function setUtmMedium(?string $utmMedium): self { $this->utmMedium = $utmMedium; return $this; }
    public function getUtmCampaign(): ?string { return $this->utmCampaign; }
    public function setUtmCampaign(?string $utmCampaign): self { $this->utmCampaign = $utmCampaign; return $this; }
    public function getUtmContent(): ?string { return $this->utmContent; }
    public function setUtmContent(?string $utmContent): self { $this->utmContent = $utmContent; return $this; }
    public function getUtmTerm(): ?string { return $this->utmTerm; }
    public function setUtmTerm(?string $utmTerm): self { $this->utmTerm = $utmTerm; return $this; }
    public function getFirstTouchAt(): ?\DateTimeInterface { return $this->firstTouchAt; }
    public function setFirstTouchAt(?\DateTimeInterface $firstTouchAt): self { $this->firstTouchAt = $firstTouchAt; return $this; }
    public function getLastTouchAt(): ?\DateTimeInterface { return $this->lastTouchAt; }
    public function setLastTouchAt(?\DateTimeInterface $lastTouchAt): self { $this->lastTouchAt = $lastTouchAt; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
