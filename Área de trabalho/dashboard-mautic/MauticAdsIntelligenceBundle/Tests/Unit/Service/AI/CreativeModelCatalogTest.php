<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Service\AI;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeModelCatalog;
use PHPUnit\Framework\TestCase;

final class CreativeModelCatalogTest extends TestCase
{
    public function testLatestGeminiModelsAreAvailableAndSelectedByDefault(): void
    {
        $models = CreativeModelCatalog::getModelsByMode();

        self::assertSame('gemini-3.6-flash', CreativeModelCatalog::defaultModel(CreativeModelCatalog::MODE_TEXT));
        self::assertSame('gemini-3.1-flash-image-preview', CreativeModelCatalog::defaultModel(CreativeModelCatalog::MODE_IMAGE));
        self::assertTrue(CreativeModelCatalog::isValidModel(CreativeModelCatalog::MODE_TEXT, 'gemini-3.5-flash-lite'));
        self::assertTrue(CreativeModelCatalog::isValidModel(CreativeModelCatalog::MODE_TEXT, 'gemini-3.1-pro-preview'));
        self::assertSame('gemini-3.6-flash', $models[CreativeModelCatalog::MODE_TEXT][0]['id']);
        self::assertSame('gemini-3.1-flash-image-preview', $models[CreativeModelCatalog::MODE_IMAGE][0]['id']);
    }
}
