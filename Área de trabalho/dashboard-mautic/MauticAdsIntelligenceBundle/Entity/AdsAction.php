<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsActionRepository;

class AdsAction
{
    private ?int $id = null;

    private ?int $recommendationId = null;

    private string $provider = '';

    private string $actionType = '';

    private string $targetType = '';

    private string $targetExternalId = '';

    private ?string $oldValue = null;

    private ?string $newValue = null;

    /** @var array<string, mixed>|null */
    private ?array $payload = null;

    private string $status = 'pending';

    private bool $requiresApproval = true;

    private ?int $approvedBy = null;

    private ?\DateTimeInterface $approvedAt = null;

    private ?\DateTimeInterface $executedAt = null;

    private ?\DateTimeInterface $failedAt = null;

    private ?string $errorMessage = null;

    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_actions')
            ->setCustomRepositoryClass(AdsActionRepository::class);

        $builder->addId();

        $builder->createField('recommendationId', Types::INTEGER)
            ->columnName('recommendation_id')
            ->nullable()
            ->build();

        $builder->addNamedField('provider', Types::STRING, 'provider');

        $builder->createField('actionType', Types::STRING)
            ->columnName('action_type')
            ->build();

        $builder->createField('targetType', Types::STRING)
            ->columnName('target_type')
            ->build();

        $builder->createField('targetExternalId', Types::STRING)
            ->columnName('target_external_id')
            ->build();

        $builder->createField('oldValue', Types::TEXT)
            ->columnName('old_value')
            ->nullable()
            ->build();

        $builder->createField('newValue', Types::TEXT)
            ->columnName('new_value')
            ->nullable()
            ->build();

        $builder->createField('payload', Types::JSON)
            ->columnName('payload')
            ->nullable()
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status');

        $builder->createField('requiresApproval', Types::BOOLEAN)
            ->columnName('requires_approval')
            ->build();

        $builder->createField('approvedBy', Types::INTEGER)
            ->columnName('approved_by')
            ->nullable()
            ->build();

        $builder->createField('approvedAt', Types::DATETIME_MUTABLE)
            ->columnName('approved_at')
            ->nullable()
            ->build();

        $builder->createField('executedAt', Types::DATETIME_MUTABLE)
            ->columnName('executed_at')
            ->nullable()
            ->build();

        $builder->createField('failedAt', Types::DATETIME_MUTABLE)
            ->columnName('failed_at')
            ->nullable()
            ->build();

        $builder->createField('errorMessage', Types::TEXT)
            ->columnName('error_message')
            ->nullable()
            ->build();

        $builder->addNamedField('createdAt', Types::DATETIME_MUTABLE, 'created_at');
    }

    public function getId(): ?int { return $this->id; }
    public function getRecommendationId(): ?int { return $this->recommendationId; }
    public function setRecommendationId(?int $recommendationId): self { $this->recommendationId = $recommendationId; return $this; }
    public function getProvider(): string { return $this->provider; }
    public function setProvider(string $provider): self { $this->provider = $provider; return $this; }
    public function getActionType(): string { return $this->actionType; }
    public function setActionType(string $actionType): self { $this->actionType = $actionType; return $this; }
    public function getTargetType(): string { return $this->targetType; }
    public function setTargetType(string $targetType): self { $this->targetType = $targetType; return $this; }
    public function getTargetExternalId(): string { return $this->targetExternalId; }
    public function setTargetExternalId(string $targetExternalId): self { $this->targetExternalId = $targetExternalId; return $this; }
    public function getOldValue(): ?string { return $this->oldValue; }
    public function setOldValue(?string $oldValue): self { $this->oldValue = $oldValue; return $this; }
    public function getNewValue(): ?string { return $this->newValue; }
    public function setNewValue(?string $newValue): self { $this->newValue = $newValue; return $this; }
    public function getPayload(): ?array { return $this->payload; }
    public function setPayload(?array $payload): self { $this->payload = $payload; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function isRequiresApproval(): bool { return $this->requiresApproval; }
    public function setRequiresApproval(bool $requiresApproval): self { $this->requiresApproval = $requiresApproval; return $this; }
    public function getApprovedBy(): ?int { return $this->approvedBy; }
    public function setApprovedBy(?int $approvedBy): self { $this->approvedBy = $approvedBy; return $this; }
    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function setApprovedAt(?\DateTimeInterface $approvedAt): self { $this->approvedAt = $approvedAt; return $this; }
    public function getExecutedAt(): ?\DateTimeInterface { return $this->executedAt; }
    public function setExecutedAt(?\DateTimeInterface $executedAt): self { $this->executedAt = $executedAt; return $this; }
    public function getFailedAt(): ?\DateTimeInterface { return $this->failedAt; }
    public function setFailedAt(?\DateTimeInterface $failedAt): self { $this->failedAt = $failedAt; return $this; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(?string $errorMessage): self { $this->errorMessage = $errorMessage; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
