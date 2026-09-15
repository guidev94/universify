<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AdsLeadStageSnapshot
{
    private ?int $id = null;

    private int $leadId = 0;

    private ?int $attributionId = null;

    private ?string $oldStage = null;

    private ?string $newStage = null;

    private ?int $oldScore = null;

    private ?int $newScore = null;

    private ?float $revenue = null;

    private \DateTimeInterface $changedAt;

    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->changedAt = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ads_lead_stage_snapshots')
            ->addIndex(['lead_id', 'changed_at'], 'idx_lead_changed')
            ->addIndex(['new_stage'], 'idx_stage');

        $builder->addId();

        $builder->createField('leadId', Types::INTEGER)
            ->columnName('lead_id')
            ->build();

        $builder->createField('attributionId', Types::INTEGER)
            ->columnName('attribution_id')
            ->nullable()
            ->build();

        $builder->createField('oldStage', Types::STRING)
            ->columnName('old_stage')
            ->nullable()
            ->build();

        $builder->createField('newStage', Types::STRING)
            ->columnName('new_stage')
            ->nullable()
            ->build();

        $builder->createField('oldScore', Types::INTEGER)
            ->columnName('old_score')
            ->nullable()
            ->build();

        $builder->createField('newScore', Types::INTEGER)
            ->columnName('new_score')
            ->nullable()
            ->build();

        $builder->createField('revenue', Types::DECIMAL)
            ->columnName('revenue')
            ->precision(14)
            ->scale(2)
            ->nullable()
            ->build();

        $builder->addNamedField('changedAt', Types::DATETIME_MUTABLE, 'changed_at');
        $builder->addNamedField('createdAt', Types::DATETIME_MUTABLE, 'created_at');
    }

    public function getId(): ?int { return $this->id; }
    public function getLeadId(): int { return $this->leadId; }
    public function setLeadId(int $leadId): self { $this->leadId = $leadId; return $this; }
    public function getAttributionId(): ?int { return $this->attributionId; }
    public function setAttributionId(?int $attributionId): self { $this->attributionId = $attributionId; return $this; }
    public function getOldStage(): ?string { return $this->oldStage; }
    public function setOldStage(?string $oldStage): self { $this->oldStage = $oldStage; return $this; }
    public function getNewStage(): ?string { return $this->newStage; }
    public function setNewStage(?string $newStage): self { $this->newStage = $newStage; return $this; }
    public function getOldScore(): ?int { return $this->oldScore; }
    public function setOldScore(?int $oldScore): self { $this->oldScore = $oldScore; return $this; }
    public function getNewScore(): ?int { return $this->newScore; }
    public function setNewScore(?int $newScore): self { $this->newScore = $newScore; return $this; }
    public function getRevenue(): ?float { return $this->revenue; }
    public function setRevenue(?float $revenue): self { $this->revenue = $revenue; return $this; }
    public function getChangedAt(): \DateTimeInterface { return $this->changedAt; }
    public function setChangedAt(\DateTimeInterface $changedAt): self { $this->changedAt = $changedAt; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
