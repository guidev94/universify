<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AdsCreativeMessage
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';

    private ?int $id = null;

    private int $conversationId = 0;

    private string $role = 'user';

    private ?string $content = null;

    private string $mediaType = 'none';

    private ?string $mediaPath = null;

    private ?string $mimeType = null;

    private ?int $fileSize = null;

    private ?string $geminiOperation = null;

    private ?string $geminiVideoName = null;

    private ?string $geminiVideoUri = null;

    private ?int $assetId = null;

    private ?string $assetUrl = null;

    private string $status = self::STATUS_COMPLETED;

    /** @var array<string, mixed>|null */
    private ?array $meta = null;

    private \DateTimeInterface $dateAdded;

    public function __construct()
    {
        $this->dateAdded = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_creative_messages')
            ->setCustomRepositoryClass(AdsCreativeMessageRepository::class);

        $builder->addId();

        $builder->createField('conversationId', Types::INTEGER)
            ->columnName('conversation_id')
            ->build();

        $builder->addNamedField('role', Types::STRING, 'role');

        $builder->createField('content', Types::TEXT)
            ->columnName('content')
            ->nullable()
            ->build();

        $builder->createField('mediaType', Types::STRING)
            ->columnName('media_type')
            ->build();

        $builder->createField('mediaPath', Types::STRING)
            ->columnName('media_path')
            ->nullable()
            ->build();

        $builder->createField('mimeType', Types::STRING)
            ->columnName('mime_type')
            ->nullable()
            ->build();

        $builder->createField('fileSize', Types::INTEGER)
            ->columnName('file_size')
            ->nullable()
            ->build();

        $builder->createField('geminiOperation', Types::STRING)
            ->columnName('gemini_operation')
            ->nullable()
            ->build();

        $builder->createField('geminiVideoName', Types::STRING)
            ->columnName('gemini_video_name')
            ->nullable()
            ->build();

        $builder->createField('geminiVideoUri', Types::STRING)
            ->columnName('gemini_video_uri')
            ->nullable()
            ->build();

        $builder->createField('assetId', Types::INTEGER)
            ->columnName('asset_id')
            ->nullable()
            ->build();

        $builder->createField('assetUrl', Types::STRING)
            ->columnName('asset_url')
            ->nullable()
            ->build();

        $builder->addNamedField('status', Types::STRING, 'status');

        $builder->createField('meta', Types::JSON)
            ->columnName('meta')
            ->nullable()
            ->build();

        $builder->createField('dateAdded', Types::DATETIME_MUTABLE)
            ->columnName('date_added')
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversationId(): int
    {
        return $this->conversationId;
    }

    public function setConversationId(int $conversationId): self
    {
        $this->conversationId = $conversationId;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getMediaPath(): ?string
    {
        return $this->mediaPath;
    }

    public function setMediaPath(?string $mediaPath): self
    {
        $this->mediaPath = $mediaPath;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getGeminiOperation(): ?string
    {
        return $this->geminiOperation;
    }

    public function setGeminiOperation(?string $geminiOperation): self
    {
        $this->geminiOperation = $geminiOperation;

        return $this;
    }

    public function getGeminiVideoName(): ?string
    {
        return $this->geminiVideoName;
    }

    public function setGeminiVideoName(?string $geminiVideoName): self
    {
        $this->geminiVideoName = $geminiVideoName;

        return $this;
    }

    public function getGeminiVideoUri(): ?string
    {
        return $this->geminiVideoUri;
    }

    public function setGeminiVideoUri(?string $geminiVideoUri): self
    {
        $this->geminiVideoUri = $geminiVideoUri;

        return $this;
    }

    public function getAssetId(): ?int
    {
        return $this->assetId;
    }

    public function setAssetId(?int $assetId): self
    {
        $this->assetId = $assetId;

        return $this;
    }

    public function getAssetUrl(): ?string
    {
        return $this->assetUrl;
    }

    public function setAssetUrl(?string $assetUrl): self
    {
        $this->assetUrl = $assetUrl;

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

    /**
     * @return array<string, mixed>|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function setMeta(?array $meta): self
    {
        $this->meta = $meta;

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

    public function hasMedia(): bool
    {
        return null !== $this->mediaPath && '' !== $this->mediaPath && 'none' !== $this->mediaType;
    }
}
