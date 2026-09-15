<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Service\AI;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeModelCatalog;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeSystemPromptCatalog;
use PHPUnit\Framework\TestCase;

final class CreativeSystemPromptCatalogTest extends TestCase
{
    public function testProvidesCompanySpecificDefaultForEveryCreativeMode(): void
    {
        $defaults = CreativeSystemPromptCatalog::defaults();

        self::assertSame(CreativeModelCatalog::getModes(), array_keys($defaults));

        foreach ($defaults as $prompt) {
            self::assertStringContainsString("Let's Work", $prompt);
            self::assertStringContainsString('rastreabilidade', $prompt);
            self::assertLessThanOrEqual(CreativeSystemPromptCatalog::MAX_LENGTH, mb_strlen($prompt));
        }
    }

    public function testEachModeHasSpecializedGuidance(): void
    {
        self::assertStringContainsString('chamada para ação', CreativeSystemPromptCatalog::defaultFor(CreativeModelCatalog::MODE_TEXT));
        self::assertStringContainsString('diretor(a) de arte', CreativeSystemPromptCatalog::defaultFor(CreativeModelCatalog::MODE_IMAGE));
        self::assertStringContainsString('movimento de câmera', CreativeSystemPromptCatalog::defaultFor(CreativeModelCatalog::MODE_VIDEO));
    }
}
