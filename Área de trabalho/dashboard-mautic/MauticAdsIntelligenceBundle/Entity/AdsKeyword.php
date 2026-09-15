<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AdsKeyword
{
    private ?int $id = null;

    private string $provider = '';

    private int $accountId = 0;

    private int $campaignId = 0;

    private ?int $adgroupId = null;

    private ?string $externalKeywordId = null;

    private string $keywordText = '';

    private ?string $matchType = null;

    private bool $negative = false;

    private ?string $status = null;

    private ?int $qualityScore = null;

    private \DateTimeInterface $createdAt;

    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_keywords');

        $builder->addId();

        $builder->addNamedField('provider', Types::STRING, 'provider');

        $builder->createField('accountId', Types::INTEGER)
            ->columnName('account_id')
            ->build();

        $builder->createField('campaignId', Types::INTEGER)
            ->columnName('campaign_id')
            ->build();

        $builder->createField('adgroupId', Types::INTEGER)
            ->columnName('adgroup_id')
            ->nullable()
            ->build();

        $builder->createField('externalKeywordId', Types::STRING)
            ->columnName('external_keyword_id')
            ->nullable()
            ->build();

        $builder->createField('keywordText', Types::STRING)
            ->columnName('keyword_text')
            ->build();

        $builder->createField('matchType', Types::STRING)
            ->columnName('match_type')
            ->nullable()
            ->build();

        $builder->createField('negative', Types::BOOLEAN)
            ->columnName('is_negative')
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status', true);

        $builder->createField('qualityScore', Types::INTEGER)
            ->columnName('quality_score')
            ->nullable()
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
    public function getCampaignId(): int { return $this->campaignId; }
    public function setCampaignId(int $campaignId): self { $this->campaignId = $campaignId; return $this; }
    public function getAdgroupId(): ?int { return $this->adgroupId; }
    public function setAdgroupId(?int $adgroupId): self { $this->adgroupId = $adgroupId; return $this; }
    public function getExternalKeywordId(): ?string { return $this->externalKeywordId; }
    public function setExternalKeywordId(?string $externalKeywordId): self { $this->externalKeywordId = $externalKeywordId; return $this; }
    public function getKeywordText(): string { return $this->keywordText; }
    public function setKeywordText(string $keywordText): self { $this->keywordText = $keywordText; return $this; }
    public function getMatchType(): ?string { return $this->matchType; }
    public function setMatchType(?string $matchType): self { $this->matchType = $matchType; return $this; }
    public function isNegative(): bool { return $this->negative; }
    public function setNegative(bool $negative): self { $this->negative = $negative; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getQualityScore(): ?int { return $this->qualityScore; }
    public function setQualityScore(?int $qualityScore): self { $this->qualityScore = $qualityScore; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
