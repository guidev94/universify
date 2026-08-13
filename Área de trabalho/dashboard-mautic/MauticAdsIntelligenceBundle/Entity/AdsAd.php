<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AdsAd
{
    private ?int $id = null;

    private string $provider = '';

    private int $accountId = 0;

    private int $campaignId = 0;

    private ?int $adgroupId = null;

    private string $externalAdId = '';

    private ?string $name = null;

    private ?string $headline = null;

    private ?string $description = null;

    private ?string $finalUrl = null;

    private ?string $status = null;

    private \DateTimeInterface $createdAt;

    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_ads')
            ->addUniqueConstraint(['provider', 'external_ad_id'], 'uniq_provider_ad');

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

        $builder->createField('externalAdId', Types::STRING)
            ->columnName('external_ad_id')
            ->build();

        $builder->addNamedField('name', Types::STRING, 'name', true);

        $builder->createField('headline', Types::TEXT)
            ->columnName('headline')
            ->nullable()
            ->build();

        $builder->createField('description', Types::TEXT)
            ->columnName('description')
            ->nullable()
            ->build();

        $builder->createField('finalUrl', Types::TEXT)
            ->columnName('final_url')
            ->nullable()
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status', true);

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
    public function getExternalAdId(): string { return $this->externalAdId; }
    public function setExternalAdId(string $externalAdId): self { $this->externalAdId = $externalAdId; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function getHeadline(): ?string { return $this->headline; }
    public function setHeadline(?string $headline): self { $this->headline = $headline; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getFinalUrl(): ?string { return $this->finalUrl; }
    public function setFinalUrl(?string $finalUrl): self { $this->finalUrl = $finalUrl; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
