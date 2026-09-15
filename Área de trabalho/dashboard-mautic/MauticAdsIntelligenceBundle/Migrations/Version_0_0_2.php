<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_2 extends AbstractMigration
{
    private string $keywordsTable = 'ads_keywords';

    protected function isApplicable(Schema $schema): bool
    {
        try {
            $table = $schema->getTable($this->concatPrefix($this->keywordsTable));

            return !$table->hasColumn('is_negative');
        } catch (SchemaException) {
            return false;
        }
    }

    protected function up(): void
    {
        $table = $this->concatPrefix($this->keywordsTable);

        $this->addSql("ALTER TABLE `{$table}` ADD `is_negative` TINYINT(1) NOT NULL DEFAULT 0 AFTER `match_type`");
    }
}
