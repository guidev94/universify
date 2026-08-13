<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Service\AI;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeModelCatalog;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\GeminiCreativeClient;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\GeminiSettingsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GeminiCreativeClientSystemPromptTest extends TestCase
{
    public function testGenerateTextSendsNativeSystemInstructionSeparatelyFromBriefing(): void
    {
        $requestBody = [];
        $client      = $this->createClient($requestBody, [
            'candidates' => [[
                'content' => ['parts' => [['text' => 'Texto criado']]],
            ]],
        ]);

        $result = $client->generateText('gemini-2.5-flash', 'Crie um anúncio de ponto eletrônico.');

        self::assertSame('Texto criado', $result['text']);
        self::assertSame('SYSTEM TEXT', $requestBody['system_instruction']['parts'][0]['text']);
        self::assertSame('Crie um anúncio de ponto eletrônico.', $requestBody['contents'][0]['parts'][0]['text']);
    }

    public function testGenerateImageSendsNativeImageSystemInstruction(): void
    {
        $requestBody = [];
        $client      = $this->createClient($requestBody, [
            'candidates' => [[
                'content' => ['parts' => [[
                    'inlineData' => [
                        'mimeType' => 'image/png',
                        'data'     => base64_encode('image-data'),
                    ],
                ]]],
            ]],
        ]);

        $result = $client->generateImage('gemini-2.5-flash-image', 'Equipe de facilities organizada.');

        self::assertSame('image-data', $result['imageBytes']);
        self::assertSame('SYSTEM IMAGE', $requestBody['system_instruction']['parts'][0]['text']);
        self::assertSame('Equipe de facilities organizada.', $requestBody['contents'][0]['parts'][0]['text']);
    }

    public function testImagenReceivesSystemContextBeforeDelimitedBriefing(): void
    {
        $requestBody = [];
        $client      = $this->createClient($requestBody, [
            'predictions' => [[
                'bytesBase64Encoded' => base64_encode('imagen-data'),
                'mimeType'           => 'image/png',
            ]],
        ]);

        $client->generateImage('imagen-4.0-generate-001', 'Gestora analisando um dashboard.');

        $composedPrompt = $requestBody['instances'][0]['prompt'];
        self::assertStringStartsWith('SYSTEM IMAGE', $composedPrompt);
        self::assertStringContainsString('--- INÍCIO DO BRIEFING ---', $composedPrompt);
        self::assertStringContainsString('Gestora analisando um dashboard.', $composedPrompt);
    }

    public function testVeoReceivesVideoSystemContextBeforeDelimitedBriefing(): void
    {
        $requestBody = [];
        $client      = $this->createClient($requestBody, ['name' => 'operations/video-1']);

        $result = $client->startVideoGeneration('veo-3.1-generate-preview', 'Cena em um hospital.');

        self::assertSame('operations/video-1', $result['operationName']);
        $composedPrompt = $requestBody['instances'][0]['prompt'];
        self::assertStringStartsWith('SYSTEM VIDEO', $composedPrompt);
        self::assertStringContainsString('Cena em um hospital.', $composedPrompt);
        self::assertStringContainsString('No dialogue, no voiceover, no singing.', $composedPrompt);
        self::assertArrayNotHasKey('personGeneration', $requestBody['parameters']);
    }

    public function testImageToVideoDoesNotSendUnsupportedPersonGeneration(): void
    {
        $requestBody = [];
        $client      = $this->createClient($requestBody, ['name' => 'operations/video-image-1']);

        $client->startVideoGeneration('veo-3.1-generate-preview', 'Transforme a imagem em vídeo.', [
            'mimeType' => 'image/png',
            'data'     => base64_encode('reference-image'),
        ]);

        self::assertArrayNotHasKey('personGeneration', $requestBody['parameters']);
    }

    public function testVideoExtensionDoesNotSendUnsupportedPersonGeneration(): void
    {
        $requestBody = [];
        $client      = $this->createClient($requestBody, ['name' => 'operations/video-extension-1']);

        $client->startVideoGeneration(
            'veo-3.1-generate-preview',
            'Continue a cena.',
            null,
            ['uri' => 'https://generativelanguage.googleapis.com/v1beta/files/video-1'],
        );

        self::assertArrayNotHasKey('personGeneration', $requestBody['parameters']);
    }

    /**
     * @param array<string, mixed> $requestBody
     * @param array<string, mixed> $responsePayload
     */
    private function createClient(array &$requestBody, array $responsePayload): GeminiCreativeClient
    {
        $settings = $this->createMock(GeminiSettingsService::class);
        $settings->method('getSettings')->willReturn([
            'configured'            => true,
            'hasApiKey'             => true,
            'apiKey'                => 'test-api-key',
            'maskedApiKey'          => 'test****-key',
            'model'                 => 'gemini-2.5-flash',
            'systemPrompts'         => [],
            'systemPromptIsDefault' => [],
        ]);
        $settings->method('getSystemPrompt')->willReturnMap([
            [CreativeModelCatalog::MODE_TEXT, 'SYSTEM TEXT'],
            [CreativeModelCatalog::MODE_IMAGE, 'SYSTEM IMAGE'],
            [CreativeModelCatalog::MODE_VIDEO, 'SYSTEM VIDEO'],
        ]);

        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$requestBody, $responsePayload): MockResponse {
                $requestBody = json_decode((string) $options['body'], true, 512, JSON_THROW_ON_ERROR);

                return new MockResponse(json_encode($responsePayload, JSON_THROW_ON_ERROR), ['http_code' => 200]);
            }
        );

        return new GeminiCreativeClient($settings, $httpClient, new NullLogger());
    }
}
