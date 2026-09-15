<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_1 extends AbstractMigration
{
    private string $initialTable = 'ads_provider_credentials';

    protected function isApplicable(Schema $schema): bool
    {
        try {
            $schema->getTable($this->concatPrefix($this->initialTable));

            return false;
        } catch (SchemaException) {
            return true;
        }
    }

    protected function up(): void
    {
        // All statements are executed in execute() to allow granular idempotence checks.
    }

    public function execute(): void
    {
        $connection = $this->entityManager->getConnection();

        $this->createAdsProviderCredentials($connection);
        $this->createAdsAccounts($connection);
        $this->createAdsCampaigns($connection);
        $this->createAdsAdgroups($connection);
        $this->createAdsAds($connection);
        $this->createAdsKeywords($connection);
        $this->createAdsMetricsDaily($connection);
        $this->createAdsLeadAttribution($connection);
        $this->createAdsLeadStageSnapshots($connection);
        $this->createAdsRecommendations($connection);
        $this->createAdsActions($connection);
        $this->createAdsActionLogs($connection);
        $this->createAdsConversionQueue($connection);
    }

    private function createAdsProviderCredentials(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_provider_credentials');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `label` VARCHAR(255) NOT NULL,
                `client_id` VARCHAR(255) NULL,
                `client_secret_encrypted` LONGTEXT NULL,
                `access_token_encrypted` LONGTEXT NULL,
                `refresh_token_encrypted` LONGTEXT NULL,
                `token_expires_at` DATETIME NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'active',
                `created_by` INT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsAccounts(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_accounts');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `credential_id` BIGINT NOT NULL,
                `external_account_id` VARCHAR(100) NOT NULL,
                `external_manager_id` VARCHAR(100) NULL,
                `name` VARCHAR(255) NOT NULL,
                `currency` VARCHAR(10) NULL,
                `timezone` VARCHAR(100) NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'active',
                `last_sync_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                UNIQUE KEY `uniq_provider_account` (`provider`, `external_account_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsCampaigns(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_campaigns');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `account_id` BIGINT NOT NULL,
                `external_campaign_id` VARCHAR(100) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `objective` VARCHAR(100) NULL,
                `channel_type` VARCHAR(100) NULL,
                `status` VARCHAR(50) NULL,
                `budget_amount` DECIMAL(14,2) NULL,
                `budget_type` VARCHAR(50) NULL,
                `started_at` DATETIME NULL,
                `ended_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                UNIQUE KEY `uniq_provider_campaign` (`provider`, `external_campaign_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsAdgroups(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_adgroups');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `account_id` BIGINT NOT NULL,
                `campaign_id` BIGINT NOT NULL,
                `external_adgroup_id` VARCHAR(100) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `status` VARCHAR(50) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                UNIQUE KEY `uniq_provider_adgroup` (`provider`, `external_adgroup_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsAds(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_ads');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `account_id` BIGINT NOT NULL,
                `campaign_id` BIGINT NOT NULL,
                `adgroup_id` BIGINT NULL,
                `external_ad_id` VARCHAR(100) NOT NULL,
                `name` VARCHAR(255) NULL,
                `headline` TEXT NULL,
                `description` TEXT NULL,
                `final_url` TEXT NULL,
                `status` VARCHAR(50) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                UNIQUE KEY `uniq_provider_ad` (`provider`, `external_ad_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsKeywords(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_keywords');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `account_id` BIGINT NOT NULL,
                `campaign_id` BIGINT NOT NULL,
                `adgroup_id` BIGINT NULL,
                `external_keyword_id` VARCHAR(100) NULL,
                `keyword_text` VARCHAR(255) NOT NULL,
                `match_type` VARCHAR(50) NULL,
                `is_negative` TINYINT(1) NOT NULL DEFAULT 0,
                `status` VARCHAR(50) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsMetricsDaily(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_metrics_daily');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `account_id` BIGINT NOT NULL,
                `campaign_id` BIGINT NULL,
                `adgroup_id` BIGINT NULL,
                `ad_id` BIGINT NULL,
                `keyword_id` BIGINT NULL,
                `metric_date` DATE NOT NULL,
                `impressions` INT NOT NULL DEFAULT 0,
                `clicks` INT NOT NULL DEFAULT 0,
                `cost` DECIMAL(14,4) NOT NULL DEFAULT 0,
                `conversions` DECIMAL(14,4) NOT NULL DEFAULT 0,
                `conversion_value` DECIMAL(14,4) NOT NULL DEFAULT 0,
                `leads` INT NOT NULL DEFAULT 0,
                `cold_leads` INT NOT NULL DEFAULT 0,
                `warm_leads` INT NOT NULL DEFAULT 0,
                `hot_leads` INT NOT NULL DEFAULT 0,
                `opportunities` INT NOT NULL DEFAULT 0,
                `customers` INT NOT NULL DEFAULT 0,
                `lost_leads` INT NOT NULL DEFAULT 0,
                `lost_reasons` JSON NULL,
                `revenue` DECIMAL(14,2) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                KEY `idx_provider_date` (`provider`, `metric_date`),
                KEY `idx_campaign_date` (`campaign_id`, `metric_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsLeadAttribution(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_lead_attribution');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `lead_id` BIGINT NOT NULL,
                `provider` VARCHAR(50) NULL,
                `source` VARCHAR(100) NULL,
                `medium` VARCHAR(100) NULL,
                `account_id` BIGINT NULL,
                `campaign_id` BIGINT NULL,
                `adgroup_id` BIGINT NULL,
                `ad_id` BIGINT NULL,
                `keyword_id` BIGINT NULL,
                `external_campaign_id` VARCHAR(100) NULL,
                `external_adgroup_id` VARCHAR(100) NULL,
                `external_ad_id` VARCHAR(100) NULL,
                `external_keyword_id` VARCHAR(100) NULL,
                `click_id` VARCHAR(255) NULL,
                `click_id_type` VARCHAR(50) NULL,
                `utm_source` VARCHAR(255) NULL,
                `utm_medium` VARCHAR(255) NULL,
                `utm_campaign` VARCHAR(255) NULL,
                `utm_content` VARCHAR(255) NULL,
                `utm_term` VARCHAR(255) NULL,
                `first_touch_at` DATETIME NULL,
                `last_touch_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                KEY `idx_lead_id` (`lead_id`),
                KEY `idx_provider_click` (`provider`, `click_id`),
                KEY `idx_campaign_id` (`campaign_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsLeadStageSnapshots(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_lead_stage_snapshots');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `lead_id` BIGINT NOT NULL,
                `attribution_id` BIGINT NULL,
                `old_stage` VARCHAR(100) NULL,
                `new_stage` VARCHAR(100) NULL,
                `old_score` INT NULL,
                `new_score` INT NULL,
                `revenue` DECIMAL(14,2) NULL,
                `changed_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                KEY `idx_lead_changed` (`lead_id`, `changed_at`),
                KEY `idx_stage` (`new_stage`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsRecommendations(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_recommendations');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NULL,
                `account_id` BIGINT NULL,
                `campaign_id` BIGINT NULL,
                `adgroup_id` BIGINT NULL,
                `ad_id` BIGINT NULL,
                `keyword_id` BIGINT NULL,
                `recommendation_type` VARCHAR(100) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL,
                `reason` TEXT NULL,
                `expected_impact` TEXT NULL,
                `risk_level` VARCHAR(30) NOT NULL DEFAULT 'medium',
                `confidence_score` DECIMAL(5,2) NULL,
                `priority_score` DECIMAL(8,2) NULL,
                `payload` JSON NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                `created_at` DATETIME NOT NULL,
                `approved_at` DATETIME NULL,
                `approved_by` INT NULL,
                `rejected_at` DATETIME NULL,
                `rejected_by` INT NULL,
                `executed_at` DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsActions(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_actions');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `recommendation_id` BIGINT NULL,
                `provider` VARCHAR(50) NOT NULL,
                `action_type` VARCHAR(100) NOT NULL,
                `target_type` VARCHAR(100) NOT NULL,
                `target_external_id` VARCHAR(100) NOT NULL,
                `old_value` TEXT NULL,
                `new_value` TEXT NULL,
                `payload` JSON NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                `requires_approval` TINYINT(1) NOT NULL DEFAULT 1,
                `approved_by` INT NULL,
                `approved_at` DATETIME NULL,
                `executed_at` DATETIME NULL,
                `failed_at` DATETIME NULL,
                `error_message` TEXT NULL,
                `created_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsActionLogs(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_action_logs');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `action_id` BIGINT NULL,
                `provider` VARCHAR(50) NULL,
                `user_id` INT NULL,
                `event_type` VARCHAR(100) NOT NULL,
                `message` TEXT NULL,
                `payload` JSON NULL,
                `created_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function createAdsConversionQueue(\Doctrine\DBAL\Connection $connection): void
    {
        $table = $this->concatPrefix('ads_conversion_queue');
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `account_id` BIGINT NOT NULL,
                `lead_id` BIGINT NOT NULL,
                `attribution_id` BIGINT NULL,
                `conversion_name` VARCHAR(255) NOT NULL,
                `conversion_value` DECIMAL(14,2) NULL,
                `conversion_currency` VARCHAR(10) NOT NULL DEFAULT 'BRL',
                `conversion_time` DATETIME NOT NULL,
                `click_id` VARCHAR(255) NULL,
                `click_id_type` VARCHAR(50) NULL,
                `hashed_email` VARCHAR(255) NULL,
                `hashed_phone` VARCHAR(255) NULL,
                `payload` JSON NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                `attempts` INT NOT NULL DEFAULT 0,
                `last_error` TEXT NULL,
                `sent_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                KEY `idx_provider_status` (`provider`, `status`),
                KEY `idx_lead_id` (`lead_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
