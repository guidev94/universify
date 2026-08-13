<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsProviderCredentialRepository;

class AdsProviderCredential
{
    private ?int $id = null;

    private string $provider = '';

    private string $label = '';

    private ?string $clientId = null;

    private ?string $clientSecretEncrypted = null;

    private ?string $accessTokenEncrypted = null;

    private ?string $refreshTokenEncrypted = null;

    private ?\DateTimeInterface $tokenExpiresAt = null;

    private string $status = 'active';

    private ?int $createdBy = null;

    private \DateTimeInterface $createdAt;

    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_provider_credentials')
            ->setCustomRepositoryClass(AdsProviderCredentialRepository::class);

        $builder->addId();

        $builder->addNamedField('provider', Types::STRING, 'provider');
        $builder->addNamedField('label', Types::STRING, 'label');

        $builder->createField('clientId', Types::STRING)
            ->columnName('client_id')
            ->nullable()
            ->build();

        $builder->createField('clientSecretEncrypted', Types::TEXT)
            ->columnName('client_secret_encrypted')
            ->nullable()
            ->build();

        $builder->createField('accessTokenEncrypted', Types::TEXT)
            ->columnName('access_token_encrypted')
            ->nullable()
            ->build();

        $builder->createField('refreshTokenEncrypted', Types::TEXT)
            ->columnName('refresh_token_encrypted')
            ->nullable()
            ->build();

        $builder->createField('tokenExpiresAt', Types::DATETIME_MUTABLE)
            ->columnName('token_expires_at')
            ->nullable()
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status');

        $builder->createField('createdBy', Types::INTEGER)
            ->columnName('created_by')
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientSecretEncrypted(): ?string
    {
        return $this->clientSecretEncrypted;
    }

    public function setClientSecretEncrypted(?string $clientSecretEncrypted): self
    {
        $this->clientSecretEncrypted = $clientSecretEncrypted;

        return $this;
    }

    public function getAccessTokenEncrypted(): ?string
    {
        return $this->accessTokenEncrypted;
    }

    public function setAccessTokenEncrypted(?string $accessTokenEncrypted): self
    {
        $this->accessTokenEncrypted = $accessTokenEncrypted;

        return $this;
    }

    public function getRefreshTokenEncrypted(): ?string
    {
        return $this->refreshTokenEncrypted;
    }

    public function setRefreshTokenEncrypted(?string $refreshTokenEncrypted): self
    {
        $this->refreshTokenEncrypted = $refreshTokenEncrypted;

        return $this;
    }

    public function getTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?\DateTimeInterface $tokenExpiresAt): self
    {
        $this->tokenExpiresAt = $tokenExpiresAt;

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

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;

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
