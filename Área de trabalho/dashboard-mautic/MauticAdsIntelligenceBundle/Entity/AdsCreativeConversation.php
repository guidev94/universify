<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AdsCreativeConversation
{
    private ?int $id = null;

    private int $userId = 0;

    private string $title = 'Nova conversa';

    private string $mode = 'text';

    private string $model = 'gemini-3.6-flash';

    private \DateTimeInterface $dateAdded;

    private ?\DateTimeInterface $dateModified = null;

    public function __construct()
    {
        $this->dateAdded    = new \DateTime();
        $this->dateModified = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_creative_conversations')
            ->setCustomRepositoryClass(AdsCreativeConversationRepository::class);

        $builder->addId();

        $builder->createField('userId', Types::INTEGER)
            ->columnName('user_id')
            ->build();

        $builder->addNamedField('title', Types::STRING, 'title');
        $builder->addNamedField('mode', Types::STRING, 'mode');
        $builder->addNamedField('model', Types::STRING, 'model');

        $builder->createField('dateAdded', Types::DATETIME_MUTABLE)
            ->columnName('date_added')
            ->build();

        $builder->createField('dateModified', Types::DATETIME_MUTABLE)
            ->columnName('date_modified')
            ->nullable()
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getDateAdded(): \DateTimeInterface
    {
        return $this->dateAdded;
    }

    public function setDateAdded(\DateTimeInterface $dateAdded): self
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(?\DateTimeInterface $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function touch(): self
    {
        $this->dateModified = new \DateTime();

        return $this;
    }
}
