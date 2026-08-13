<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AdsConversionQueue
{
    private ?int $id = null;

    private string $provider = '';

    private int $accountId = 0;

    private int $leadId = 0;

    private ?int $attributionId = null;

    private string $conversionName = '';

    private ?float $conversionValue = null;

    private string $conversionCurrency = 'BRL';

    private \DateTimeInterface $conversionTime;

    private ?string $clickId = null;

    private ?string $clickIdType = null;

    private ?string $hashedEmail = null;

    private ?string $hashedPhone = null;

    /** @var array<string, mixed>|null */
    private ?array $payload = null;

    private string $status = 'pending';

    private int $attempts = 0;

    private ?string $lastError = null;

    private ?\DateTimeInterface $sentAt = null;

    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt      = new \DateTime();
        $this->conversionTime = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_conversion_queue')
            ->addIndex(['provider', 'status'], 'idx_provider_status')
            ->addIndex(['lead_id'], 'idx_lead_id');

        $builder->addId();

        $builder->addNamedField('provider', Types::STRING, 'provider');

        $builder->createField('accountId', Types::INTEGER)
            ->columnName('account_id')
            ->build();

        $builder->createField('leadId', Types::INTEGER)
            ->columnName('lead_id')
            ->build();

        $builder->createField('attributionId', Types::INTEGER)
            ->columnName('attribution_id')
            ->nullable()
            ->build();

        $builder->createField('conversionName', Types::STRING)
            ->columnName('conversion_name')
            ->build();

        $builder->createField('conversionValue', Types::DECIMAL)
            ->columnName('conversion_value')
            ->precision(14)
            ->scale(2)
            ->nullable()
            ->build();

        $builder->createField('conversionCurrency', Types::STRING)
            ->columnName('conversion_currency')
            ->build();

        $builder->createField('conversionTime', Types::DATETIME_MUTABLE)
            ->columnName('conversion_time')
            ->build();

        $builder->createField('clickId', Types::STRING)
            ->columnName('click_id')
            ->nullable()
            ->build();

        $builder->createField('clickIdType', Types::STRING)
            ->columnName('click_id_type')
            ->nullable()
            ->build();

        $builder->createField('hashedEmail', Types::STRING)
            ->columnName('hashed_email')
            ->nullable()
            ->build();

        $builder->createField('hashedPhone', Types::STRING)
            ->columnName('hashed_phone')
            ->nullable()
            ->build();

        $builder->createField('payload', Types::JSON)
            ->columnName('payload')
            ->nullable()
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status');
        $builder->addNamedField('attempts', Types::INTEGER, 'attempts');

        $builder->createField('lastError', Types::TEXT)
            ->columnName('last_error')
            ->nullable()
            ->build();

        $builder->createField('sentAt', Types::DATETIME_MUTABLE)
            ->columnName('sent_at')
            ->nullable()
            ->build();

        $builder->addNamedField('createdAt', Types::DATETIME_MUTABLE, 'created_at');
    }

    public function getId(): ?int { return $this->id; }
    public function getProvider(): string { return $this->provider; }
    public function setProvider(string $provider): self { $this->provider = $provider; return $this; }
    public function getAccountId(): int { return $this->accountId; }
    public function setAccountId(int $accountId): self { $this->accountId = $accountId; return $this; }
    public function getLeadId(): int { return $this->leadId; }
    public function setLeadId(int $leadId): self { $this->leadId = $leadId; return $this; }
    public function getAttributionId(): ?int { return $this->attributionId; }
    public function setAttributionId(?int $attributionId): self { $this->attributionId = $attributionId; return $this; }
    public function getConversionName(): string { return $this->conversionName; }
    public function setConversionName(string $conversionName): self { $this->conversionName = $conversionName; return $this; }
    public function getConversionValue(): ?float { return $this->conversionValue; }
    public function setConversionValue(?float $conversionValue): self { $this->conversionValue = $conversionValue; return $this; }
    public function getConversionCurrency(): string { return $this->conversionCurrency; }
    public function setConversionCurrency(string $conversionCurrency): self { $this->conversionCurrency = $conversionCurrency; return $this; }
    public function getConversionTime(): \DateTimeInterface { return $this->conversionTime; }
    public function setConversionTime(\DateTimeInterface $conversionTime): self { $this->conversionTime = $conversionTime; return $this; }
    public function getClickId(): ?string { return $this->clickId; }
    public function setClickId(?string $clickId): self { $this->clickId = $clickId; return $this; }
    public function getClickIdType(): ?string { return $this->clickIdType; }
    public function setClickIdType(?string $clickIdType): self { $this->clickIdType = $clickIdType; return $this; }
    public function getHashedEmail(): ?string { return $this->hashedEmail; }
    public function setHashedEmail(?string $hashedEmail): self { $this->hashedEmail = $hashedEmail; return $this; }
    public function getHashedPhone(): ?string { return $this->hashedPhone; }
    public function setHashedPhone(?string $hashedPhone): self { $this->hashedPhone = $hashedPhone; return $this; }
    public function getPayload(): ?array { return $this->payload; }
    public function setPayload(?array $payload): self { $this->payload = $payload; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getAttempts(): int { return $this->attempts; }
    public function setAttempts(int $attempts): self { $this->attempts = $attempts; return $this; }
    public function getLastError(): ?string { return $this->lastError; }
    public function setLastError(?string $lastError): self { $this->lastError = $lastError; return $this; }
    public function getSentAt(): ?\DateTimeInterface { return $this->sentAt; }
    public function setSentAt(?\DateTimeInterface $sentAt): self { $this->sentAt = $sentAt; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
