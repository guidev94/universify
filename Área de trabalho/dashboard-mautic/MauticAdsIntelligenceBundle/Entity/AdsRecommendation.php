<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsRecommendationRepository;

class AdsRecommendation
{
    private ?int $id = null;

    private ?string $provider = null;

    private ?int $accountId = null;

    private ?int $campaignId = null;

    private ?int $adgroupId = null;

    private ?int $adId = null;

    private ?int $keywordId = null;

    private string $recommendationType = '';

    private string $title = '';

    private string $description = '';

    private ?string $reason = null;

    private ?string $expectedImpact = null;

    private string $riskLevel = 'medium';

    private ?float $confidenceScore = null;

    private ?float $priorityScore = null;

    /** @var array<string, mixed>|null */
    private ?array $payload = null;

    private string $status = 'pending';

    private \DateTimeInterface $createdAt;

    private ?\DateTimeInterface $approvedAt = null;

    private ?int $approvedBy = null;

    private ?\DateTimeInterface $rejectedAt = null;

    private ?int $rejectedBy = null;

    private ?\DateTimeInterface $executedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_recommendations')
            ->setCustomRepositoryClass(AdsRecommendationRepository::class);

        $builder->addId();

        $builder->addNamedField('provider', Types::STRING, 'provider', true);

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

        $builder->createField('recommendationType', Types::STRING)
            ->columnName('recommendation_type')
            ->build();

        $builder->addNamedField('title', Types::STRING, 'title');

        $builder->createField('description', Types::TEXT)
            ->columnName('description')
            ->build();

        $builder->createField('reason', Types::TEXT)
            ->columnName('reason')
            ->nullable()
            ->build();

        $builder->createField('expectedImpact', Types::TEXT)
            ->columnName('expected_impact')
            ->nullable()
            ->build();

        $builder->createField('riskLevel', Types::STRING)
            ->columnName('risk_level')
            ->build();

        $builder->createField('confidenceScore', Types::DECIMAL)
            ->columnName('confidence_score')
            ->precision(5)
            ->scale(2)
            ->nullable()
            ->build();

        $builder->createField('priorityScore', Types::DECIMAL)
            ->columnName('priority_score')
            ->precision(8)
            ->scale(2)
            ->nullable()
            ->build();

        $builder->createField('payload', Types::JSON)
            ->columnName('payload')
            ->nullable()
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status');
        $builder->addNamedField('createdAt', Types::DATETIME_MUTABLE, 'created_at');

        $builder->createField('approvedAt', Types::DATETIME_MUTABLE)
            ->columnName('approved_at')
            ->nullable()
            ->build();

        $builder->createField('approvedBy', Types::INTEGER)
            ->columnName('approved_by')
            ->nullable()
            ->build();

        $builder->createField('rejectedAt', Types::DATETIME_MUTABLE)
            ->columnName('rejected_at')
            ->nullable()
            ->build();

        $builder->createField('rejectedBy', Types::INTEGER)
            ->columnName('rejected_by')
            ->nullable()
            ->build();

        $builder->createField('executedAt', Types::DATETIME_MUTABLE)
            ->columnName('executed_at')
            ->nullable()
            ->build();
    }

    public function getId(): ?int { return $this->id; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $provider): self { $this->provider = $provider; return $this; }
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
    public function getRecommendationType(): string { return $this->recommendationType; }
    public function setRecommendationType(string $recommendationType): self { $this->recommendationType = $recommendationType; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): self { $this->reason = $reason; return $this; }
    public function getExpectedImpact(): ?string { return $this->expectedImpact; }
    public function setExpectedImpact(?string $expectedImpact): self { $this->expectedImpact = $expectedImpact; return $this; }
    public function getRiskLevel(): string { return $this->riskLevel; }
    public function setRiskLevel(string $riskLevel): self { $this->riskLevel = $riskLevel; return $this; }
    public function getConfidenceScore(): ?float { return $this->confidenceScore; }
    public function setConfidenceScore(?float $confidenceScore): self { $this->confidenceScore = $confidenceScore; return $this; }
    public function getPriorityScore(): ?float { return $this->priorityScore; }
    public function setPriorityScore(?float $priorityScore): self { $this->priorityScore = $priorityScore; return $this; }
    public function getPayload(): ?array { return $this->payload; }
    public function setPayload(?array $payload): self { $this->payload = $payload; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function setApprovedAt(?\DateTimeInterface $approvedAt): self { $this->approvedAt = $approvedAt; return $this; }
    public function getApprovedBy(): ?int { return $this->approvedBy; }
    public function setApprovedBy(?int $approvedBy): self { $this->approvedBy = $approvedBy; return $this; }
    public function getRejectedAt(): ?\DateTimeInterface { return $this->rejectedAt; }
    public function setRejectedAt(?\DateTimeInterface $rejectedAt): self { $this->rejectedAt = $rejectedAt; return $this; }
    public function getRejectedBy(): ?int { return $this->rejectedBy; }
    public function setRejectedBy(?int $rejectedBy): self { $this->rejectedBy = $rejectedBy; return $this; }
    public function getExecutedAt(): ?\DateTimeInterface { return $this->executedAt; }
    public function setExecutedAt(?\DateTimeInterface $executedAt): self { $this->executedAt = $executedAt; return $this; }
}
