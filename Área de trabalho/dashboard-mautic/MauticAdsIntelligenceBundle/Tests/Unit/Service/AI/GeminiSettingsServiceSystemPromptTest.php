<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Service\AI;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeModelCatalog;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeSystemPromptCatalog;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\GeminiSettingsService;
use PHPUnit\Framework\TestCase;

final class GeminiSettingsServiceSystemPromptTest extends TestCase
{
    public function testReturnsOptimizedDefaultsWhenNoCustomPromptsAreStored(): void
    {
        $service  = $this->createService(null);
        $settings = $service->getSettings();

        self::assertSame(CreativeSystemPromptCatalog::defaults(), $settings['systemPrompts']);
        self::assertSame([
            CreativeModelCatalog::MODE_TEXT  => true,
            CreativeModelCatalog::MODE_IMAGE => true,
            CreativeModelCatalog::MODE_VIDEO => true,
        ], $settings['systemPromptIsDefault']);
    }

    public function testReturnsStoredCustomPromptOnlyForItsMode(): void
    {
        $integration = (new Integration())->setApiKeys([
            'creative_system_prompt_text' => 'Prompt de texto personalizado',
        ]);
        $service      = $this->createService($integration);
        $settings     = $service->getSettings();

        self::assertSame('Prompt de texto personalizado', $settings['systemPrompts'][CreativeModelCatalog::MODE_TEXT]);
        self::assertFalse($settings['systemPromptIsDefault'][CreativeModelCatalog::MODE_TEXT]);
        self::assertTrue($settings['systemPromptIsDefault'][CreativeModelCatalog::MODE_IMAGE]);
        self::assertSame(
            CreativeSystemPromptCatalog::defaultFor(CreativeModelCatalog::MODE_VIDEO),
            $settings['systemPrompts'][CreativeModelCatalog::MODE_VIDEO]
        );
    }

    public function testBlankPromptRemovesCustomizationAndRestoresDefault(): void
    {
        $integration = (new Integration())->setApiKeys([
            'gemini_api_key'              => 'current-key',
            'creative_system_prompt_text' => 'Prompt antigo',
        ]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service       = $this->createService($integration, $entityManager);

        $entityManager->expects(self::once())->method('persist')->with($integration);
        $entityManager->expects(self::once())->method('flush');

        $service->save(null, GeminiSettingsService::DEFAULT_MODEL, [
            CreativeModelCatalog::MODE_TEXT => '',
        ]);

        self::assertArrayNotHasKey('creative_system_prompt_text', $integration->getApiKeys());
        self::assertArrayHasKey('gemini_api_key', $integration->getApiKeys());
    }

    public function testRejectsPromptAboveMaximumLength(): void
    {
        $service = $this->createService(new Integration());

        $this->expectException(\InvalidArgumentException::class);
        $service->save(null, GeminiSettingsService::DEFAULT_MODEL, [
            CreativeModelCatalog::MODE_TEXT => str_repeat('a', CreativeSystemPromptCatalog::MAX_LENGTH + 1),
        ]);
    }

    private function createService(?Integration $integration, ?EntityManagerInterface $entityManager = null): GeminiSettingsService
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findOneBy')->willReturn($integration);

        $entityManager ??= $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(Integration::class)->willReturn($repository);

        $encryption = $this->createMock(EncryptionHelper::class);
        $encryption->method('decrypt')->willReturnCallback(static fn (string $value): string => $value);
        $encryption->method('encrypt')->willReturnCallback(static fn (string $value): string => 'encrypted:'.$value);

        return new GeminiSettingsService($entityManager, $encryption);
    }
}
