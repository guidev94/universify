<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_3 extends AbstractMigration
{
    private string $conversationsTable = 'ads_creative_conversations';

    protected function isApplicable(Schema $schema): bool
    {
        try {
            $schema->getTable($this->concatPrefix($this->conversationsTable));

            return false;
        } catch (SchemaException) {
            return true;
        }
    }

    protected function up(): void
    {
        $conversations = $this->concatPrefix('ads_creative_conversations');
        $messages      = $this->concatPrefix('ads_creative_messages');

        $this->addSql("
            CREATE TABLE IF NOT EXISTS `{$conversations}` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `title` VARCHAR(255) NOT NULL DEFAULT 'Nova conversa',
                `mode` VARCHAR(20) NOT NULL DEFAULT 'text',
                `model` VARCHAR(100) NOT NULL DEFAULT 'gemini-3.5-flash',
                `date_added` DATETIME NOT NULL,
                `date_modified` DATETIME NULL,
                KEY `idx_ads_creative_conv_user` (`user_id`),
                KEY `idx_ads_creative_conv_modified` (`date_modified`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS `{$messages}` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `conversation_id` INT NOT NULL,
                `role` VARCHAR(20) NOT NULL,
                `content` LONGTEXT NULL,
                `media_type` VARCHAR(20) NOT NULL DEFAULT 'none',
                `media_path` VARCHAR(500) NULL,
                `mime_type` VARCHAR(100) NULL,
                `file_size` INT NULL,
                `gemini_operation` VARCHAR(500) NULL,
                `gemini_video_name` VARCHAR(500) NULL,
                `gemini_video_uri` VARCHAR(1000) NULL,
                `asset_id` INT NULL,
                `asset_url` VARCHAR(1000) NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'completed',
                `meta` JSON NULL,
                `date_added` DATETIME NOT NULL,
                KEY `idx_ads_creative_msg_conv` (`conversation_id`),
                KEY `idx_ads_creative_msg_status` (`status`),
                CONSTRAINT `fk_ads_creative_msg_conv`
                    FOREIGN KEY (`conversation_id`) REFERENCES `{$conversations}` (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
