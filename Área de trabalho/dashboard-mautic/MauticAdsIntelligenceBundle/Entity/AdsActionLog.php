<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AdsActionLog
{
    private ?int $id = null;

    private ?int $actionId = null;

    private ?string $provider = null;

    private ?int $userId = null;

    private string $eventType = '';

    private ?string $message = null;

    /** @var array<string, mixed>|null */
    private ?array $payload = null;

    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_action_logs');

        $builder->addId();

        $builder->createField('actionId', Types::INTEGER)
            ->columnName('action_id')
            ->nullable()
            ->build();

        $builder->addNamedField('provider', Types::STRING, 'provider', true);

        $builder->createField('userId', Types::INTEGER)
            ->columnName('user_id')
            ->nullable()
            ->build();

        $builder->createField('eventType', Types::STRING)
            ->columnName('event_type')
            ->build();

        $builder->createField('message', Types::TEXT)
            ->columnName('message')
            ->nullable()
            ->build();

        $builder->createField('payload', Types::JSON)
            ->columnName('payload')
            ->nullable()
            ->build();

        $builder->addNamedField('createdAt', Types::DATETIME_MUTABLE, 'created_at');
    }

    public function getId(): ?int { return $this->id; }
    public function getActionId(): ?int { return $this->actionId; }
    public function setActionId(?int $actionId): self { $this->actionId = $actionId; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $provider): self { $this->provider = $provider; return $this; }
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): self { $this->userId = $userId; return $this; }
    public function getEventType(): string { return $this->eventType; }
    public function setEventType(string $eventType): self { $this->eventType = $eventType; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $message): self { $this->message = $message; return $this; }
    public function getPayload(): ?array { return $this->payload; }
    public function setPayload(?array $payload): self { $this->payload = $payload; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
