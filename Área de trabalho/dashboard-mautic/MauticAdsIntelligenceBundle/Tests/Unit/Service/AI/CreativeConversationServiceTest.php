<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Service\AI;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeConversation;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeConversationRepository;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeMessage;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeMessageRepository;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeConversationService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeMediaService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\GeminiCreativeClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CreativeConversationServiceTest extends TestCase
{
    public function testCompletedVideoIsExplicitlyPersistedDuringSsePolling(): void
    {
        $conversation = (new AdsCreativeConversation())
            ->setUserId(7)
            ->setMode('video')
            ->setModel('veo-3.1-generate-preview');
        $message = (new AdsCreativeMessage())
            ->setConversationId(11)
            ->setRole('assistant')
            ->setStatus(AdsCreativeMessage::STATUS_PENDING)
            ->setMediaType('video')
            ->setContent('Gerando vídeo…')
            ->setGeminiOperation('models/veo/operations/example');

        self::setEntityId($conversation, 11);
        self::setEntityId($message, 24);

        $conversationRepository = $this->createMock(AdsCreativeConversationRepository::class);
        $conversationRepository->method('findOneByIdAndUser')->with(11, 7)->willReturn($conversation);

        $messageRepository = $this->createMock(AdsCreativeMessageRepository::class);
        $messageRepository->method('find')->with(24)->willReturn($message);

        $persisted     = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            static fn (string $class) => AdsCreativeConversation::class === $class
                ? $conversationRepository
                : $messageRepository,
        );
        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        $geminiClient = $this->createMock(GeminiCreativeClient::class);
        $geminiClient->method('pollVideoOperation')->willReturn([
            'done'       => true,
            'error'      => null,
            'videoUri'   => 'https://example.invalid/video.mp4',
            'videoName'  => 'generated-video',
            'videoBytes' => 'video-bytes',
        ]);

        $mediaService = $this->createMock(CreativeMediaService::class);
        $mediaService->method('storeBinary')->with(11, 'video-bytes', 'mp4', 'video/mp4')->willReturn([
            'relativePath' => 'ads-intelligence/creatives/11/example.mp4',
            'mimeType'     => 'video/mp4',
            'fileSize'     => 11,
        ]);

        $service = new CreativeConversationService($entityManager, $geminiClient, $mediaService, new NullLogger());
        $result  = $service->pollMessage(7, 24);

        self::assertSame([$conversation, $message], $persisted);
        self::assertTrue($result['done']);
        self::assertSame('completed', $result['message']['status']);
        self::assertSame('Vídeo gerado com sucesso.', $result['message']['content']);
        self::assertTrue($result['message']['hasMedia']);
        self::assertSame('ads-intelligence/creatives/11/example.mp4', $message->getMediaPath());
    }

    private static function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}
