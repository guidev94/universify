<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaignRepository;

class AdsCampaign
{
    private ?int $id = null;

    private string $provider = '';

    private int $accountId = 0;

    private string $externalCampaignId = '';

    private string $name = '';

    private ?string $objective = null;

    private ?string $channelType = null;

    private ?string $status = null;

    private ?float $budgetAmount = null;

    private ?string $budgetType = null;

    private ?\DateTimeInterface $startedAt = null;

    private ?\DateTimeInterface $endedAt = null;

    private \DateTimeInterface $createdAt;

    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_campaigns')
            ->setCustomRepositoryClass(AdsCampaignRepository::class)
            ->addUniqueConstraint(['provider', 'external_campaign_id'], 'uniq_provider_campaign');

        $builder->addId();

        $builder->addNamedField('provider', Types::STRING, 'provider');

        $builder->createField('accountId', Types::INTEGER)
            ->columnName('account_id')
            ->build();

        $builder->createField('externalCampaignId', Types::STRING)
            ->columnName('external_campaign_id')
            ->build();

        $builder->addNamedField('name', Types::STRING, 'name');

        $builder->createField('objective', Types::STRING)
            ->columnName('objective')
            ->nullable()
            ->build();

        $builder->createField('channelType', Types::STRING)
            ->columnName('channel_type')
            ->nullable()
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status', true);

        $builder->createField('budgetAmount', Types::DECIMAL)
            ->columnName('budget_amount')
            ->precision(14)
            ->scale(2)
            ->nullable()
            ->build();

        $builder->createField('budgetType', Types::STRING)
            ->columnName('budget_type')
            ->nullable()
            ->build();

        $builder->createField('startedAt', Types::DATETIME_MUTABLE)
            ->columnName('started_at')
            ->nullable()
            ->build();

        $builder->createField('endedAt', Types::DATETIME_MUTABLE)
            ->columnName('ended_at')
            ->nullable()
            ->build();

        $builder->addNamedField('createdAt', Types::DATETIME_MUTABLE, 'created_at');

        $builder->createField('updatedAt', Types::DATETIME_MUTABLE)
            ->columnName('updated_at')
            ->nullable()
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function setAccountId(int $accountId): self
    {
        $this->accountId = $accountId;

        return $this;
    }

    public function getExternalCampaignId(): string
    {
        return $this->externalCampaignId;
    }

    public function setExternalCampaignId(string $externalCampaignId): self
    {
        $this->externalCampaignId = $externalCampaignId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(?string $objective): self
    {
        $this->objective = $objective;

        return $this;
    }

    public function getChannelType(): ?string
    {
        return $this->channelType;
    }

    public function setChannelType(?string $channelType): self
    {
        $this->channelType = $channelType;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getBudgetAmount(): ?float
    {
        return $this->budgetAmount;
    }

    public function setBudgetAmount(?float $budgetAmount): self
    {
        $this->budgetAmount = $budgetAmount;

        return $this;
    }

    public function getBudgetType(): ?string
    {
        return $this->budgetType;
    }

    public function setBudgetType(?string $budgetType): self
    {
        $this->budgetType = $budgetType;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeInterface
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeInterface $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
