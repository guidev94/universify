<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Service\AI;

use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeConversationService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeGenerationStreamService;
use PHPUnit\Framework\TestCase;

class CreativeGenerationStreamServiceTest extends TestCase
{
    public function testStreamsImmediateGenerationToCompletion(): void
    {
        $conversationService = $this->createMock(CreativeConversationService::class);
        $conversationService->expects(self::once())
            ->method('sendMessage')
            ->with(7, 11, 'Create an ad', 'image', 'image-model', false, null, '1:1')
            ->willReturn([
                'pending' => false,
                'message' => ['id' => 31, 'status' => 'completed'],
            ]);

        $events  = [];
        $service = new CreativeGenerationStreamService($conversationService, 0, 1);
        $service->streamMessage(
            7,
            11,
            'Create an ad',
            'image',
            'image-model',
            false,
            null,
            '1:1',
            static function (string $event, array $data) use (&$events): void {
                $events[] = [$event, $data];
            },
            static fn (): bool => false,
        );

        self::assertSame(
            [CreativeGenerationStreamService::EVENT_STARTED, CreativeGenerationStreamService::EVENT_COMPLETED],
            array_column($events, 0),
        );
        self::assertSame(31, $events[1][1]['message']['id']);
    }

    public function testStreamsPendingGenerationUntilCompletion(): void
    {
        $conversationService = $this->createMock(CreativeConversationService::class);
        $conversationService->expects(self::once())
            ->method('sendMessage')
            ->willReturn([
                'pending' => true,
                'message' => ['id' => 41, 'status' => 'pending'],
            ]);
        $conversationService->expects(self::once())
            ->method('pollMessage')
            ->with(7, 41)
            ->willReturn([
                'done'    => true,
                'message' => ['id' => 41, 'status' => 'completed'],
            ]);

        $events  = [];
        $service = new CreativeGenerationStreamService($conversationService, 0, 1);
        $service->streamMessage(
            7,
            11,
            'Create a video',
            'video',
            'video-model',
            false,
            null,
            '16:9',
            static function (string $event, array $data) use (&$events): void {
                $events[] = [$event, $data];
            },
            static fn (): bool => false,
        );

        self::assertSame(
            [
                CreativeGenerationStreamService::EVENT_STARTED,
                CreativeGenerationStreamService::EVENT_PROGRESS,
                CreativeGenerationStreamService::EVENT_COMPLETED,
            ],
            array_column($events, 0),
        );
    }

    public function testEmitsFailedForTerminalFailedMessage(): void
    {
        $conversationService = $this->createMock(CreativeConversationService::class);
        $conversationService->method('replayMessage')->willReturn([
            'pending'     => false,
            'userMessage' => ['id' => 50, 'status' => 'completed'],
            'message'     => ['id' => 51, 'status' => 'failed'],
        ]);

        $events  = [];
        $service = new CreativeGenerationStreamService($conversationService, 0, 1);
        $service->streamReplay(
            7,
            51,
            static function (string $event, array $data) use (&$events): void {
                $events[] = [$event, $data];
            },
            static fn (): bool => false,
        );

        self::assertSame(CreativeGenerationStreamService::EVENT_FAILED, $events[1][0]);
        self::assertArrayNotHasKey('userMessage', $events[1][1]);
    }

    public function testStopsPollingAfterClientDisconnects(): void
    {
        $conversationService = $this->createMock(CreativeConversationService::class);
        $conversationService->method('sendMessage')->willReturn([
            'pending' => true,
            'message' => ['id' => 61, 'status' => 'pending'],
        ]);
        $conversationService->expects(self::never())->method('pollMessage');

        $events  = [];
        $service = new CreativeGenerationStreamService($conversationService, 0, 1);
        $service->streamMessage(
            7,
            11,
            'Create a video',
            'video',
            'video-model',
            false,
            null,
            '16:9',
            static function (string $event, array $data) use (&$events): void {
                $events[] = [$event, $data];
            },
            static fn (): bool => true,
        );

        self::assertSame(
            [CreativeGenerationStreamService::EVENT_STARTED, CreativeGenerationStreamService::EVENT_PROGRESS],
            array_column($events, 0),
        );
    }
}
