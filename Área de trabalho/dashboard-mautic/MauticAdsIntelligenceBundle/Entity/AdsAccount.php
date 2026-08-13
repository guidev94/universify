<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccountRepository;

class AdsAccount
{
    private ?int $id = null;

    private string $provider = '';

    private int $credentialId = 0;

    private string $externalAccountId = '';

    private ?string $externalManagerId = null;

    private string $name = '';

    private ?string $currency = null;

    private ?string $timezone = null;

    private string $status = 'active';

    private ?\DateTimeInterface $lastSyncAt = null;

    private \DateTimeInterface $createdAt;

    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_accounts')
            ->setCustomRepositoryClass(AdsAccountRepository::class)
            ->addUniqueConstraint(['provider', 'external_account_id'], 'uniq_provider_account');

        $builder->addId();

        $builder->addNamedField('provider', Types::STRING, 'provider');

        $builder->createField('credentialId', Types::INTEGER)
            ->columnName('credential_id')
            ->build();

        $builder->createField('externalAccountId', Types::STRING)
            ->columnName('external_account_id')
            ->build();

        $builder->createField('externalManagerId', Types::STRING)
            ->columnName('external_manager_id')
            ->nullable()
            ->build();

        $builder->addNamedField('name', Types::STRING, 'name');

        $builder->createField('currency', Types::STRING)
            ->columnName('currency')
            ->nullable()
            ->build();

        $builder->createField('timezone', Types::STRING)
            ->columnName('timezone')
            ->nullable()
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status');

        $builder->createField('lastSyncAt', Types::DATETIME_MUTABLE)
            ->columnName('last_sync_at')
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

    public function getCredentialId(): int
    {
        return $this->credentialId;
    }

    public function setCredentialId(int $credentialId): self
    {
        $this->credentialId = $credentialId;

        return $this;
    }

    public function getExternalAccountId(): string
    {
        return $this->externalAccountId;
    }

    public function setExternalAccountId(string $externalAccountId): self
    {
        $this->externalAccountId = $externalAccountId;

        return $this;
    }

    public function getExternalManagerId(): ?string
    {
        return $this->externalManagerId;
    }

    public function setExternalManagerId(?string $externalManagerId): self
    {
        $this->externalManagerId = $externalManagerId;

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

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getLastSyncAt(): ?\DateTimeInterface
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeInterface $lastSyncAt): self
    {
        $this->lastSyncAt = $lastSyncAt;

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
