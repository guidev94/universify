<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeConversation;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeConversationRepository;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeMessage;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeMessageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreativeConversationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GeminiCreativeClient $geminiClient,
        private readonly CreativeMediaService $mediaService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listConversations(int $userId): array
    {
        $conversations = $this->getConversationRepository()->findByUser($userId);

        return array_map(fn (AdsCreativeConversation $c) => $this->serializeConversation($c), $conversations);
    }

    /**
     * @return array<string, mixed>
     */
    public function createConversation(int $userId, string $mode = CreativeModelCatalog::MODE_TEXT, ?string $model = null): array
    {
        if (!CreativeModelCatalog::isValidMode($mode)) {
            $mode = CreativeModelCatalog::MODE_TEXT;
        }

        $model = $model ?: CreativeModelCatalog::defaultModel($mode);
        if (!CreativeModelCatalog::isValidModel($mode, $model)) {
            $model = CreativeModelCatalog::defaultModel($mode);
        }

        $conversation = (new AdsCreativeConversation())
            ->setUserId($userId)
            ->setMode($mode)
            ->setModel($model)
            ->setTitle('Nova conversa');

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $this->serializeConversation($conversation);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConversation(int $userId, int $conversationId): array
    {
        $conversation = $this->requireConversation($userId, $conversationId);
        $messages     = $this->getMessageRepository()->findByConversation($conversationId);

        return [
            'conversation' => $this->serializeConversation($conversation),
            'messages'     => array_map(fn (AdsCreativeMessage $m) => $this->serializeMessage($m), $messages),
            'canExtend'    => null !== $this->getMessageRepository()->findLastAssistantWithVideo($conversationId),
        ];
    }

    public function deleteConversation(int $userId, int $conversationId): void
    {
        $conversation = $this->requireConversation($userId, $conversationId);
        $this->entityManager->remove($conversation);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMessage(
        int $userId,
        int $conversationId,
        string $prompt,
        string $mode,
        string $model,
        bool $extendVideo = false,
        ?UploadedFile $referenceImage = null,
        string $aspectRatio = '16:9',
    ): array {
        if (!$this->geminiClient->isConfigured()) {
            throw new \RuntimeException('Configure a chave da API Gemini em Ads Intelligence → Settings.');
        }

        if (!CreativeModelCatalog::isValidMode($mode)) {
            throw new \InvalidArgumentException('Modo inválido.');
        }

        if (!CreativeModelCatalog::isValidModel($mode, $model)) {
            throw new \InvalidArgumentException('Modelo inválido para o modo selecionado.');
        }

        $prompt = trim($prompt);
        if ('' === $prompt) {
            throw new \InvalidArgumentException('Informe um prompt.');
        }

        $conversation = $this->requireConversation($userId, $conversationId);
        $conversation->setMode($mode)->setModel($model)->touch();

        if ('Nova conversa' === $conversation->getTitle()) {
            $conversation->setTitle(mb_substr($prompt, 0, 60));
        }

        $userMessage = (new AdsCreativeMessage())
            ->setConversationId((int) $conversation->getId())
            ->setRole('user')
            ->setContent($prompt)
            ->setMediaType('none')
            ->setStatus(AdsCreativeMessage::STATUS_COMPLETED)
            ->setMeta([
                'mode'         => $mode,
                'model'        => $model,
                'extend_video' => $extendVideo,
                'aspect_ratio' => $aspectRatio,
            ]);

        $referenceInline = null;
        if ($referenceImage instanceof UploadedFile) {
            $bytes = file_get_contents($referenceImage->getPathname());
            if (false === $bytes || '' === $bytes) {
                throw new \RuntimeException('Não foi possível ler a imagem de referência.');
            }

            $mime = $referenceImage->getMimeType() ?: 'image/png';
            $stored = $this->mediaService->storeBinary(
                (int) $conversation->getId(),
                $bytes,
                $this->mediaService->extensionFromMime($mime),
                $mime
            );

            $userMessage
                ->setMediaType('image')
                ->setMediaPath($stored['relativePath'])
                ->setMimeType($stored['mimeType'])
                ->setFileSize($stored['fileSize']);

            $referenceInline = [
                'mimeType' => $mime,
                'data'     => base64_encode($bytes),
            ];
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->persist($userMessage);
        $this->entityManager->flush();

        return match ($mode) {
            CreativeModelCatalog::MODE_TEXT  => $this->handleText($conversation, $userMessage, $model, $prompt),
            CreativeModelCatalog::MODE_IMAGE => $this->handleImage($conversation, $userMessage, $model, $prompt),
            CreativeModelCatalog::MODE_VIDEO => $this->handleVideo(
                $conversation,
                $userMessage,
                $model,
                $prompt,
                $extendVideo,
                $referenceInline,
                $aspectRatio
            ),
            default => throw new \InvalidArgumentException('Modo inválido.'),
        };
    }

    /**
     * Regenerates from an existing user/assistant message without creating a new chat item.
     *
     * @return array<string, mixed>
     */
    public function replayMessage(int $userId, int $messageId): array
    {
        if (!$this->geminiClient->isConfigured()) {
            throw new \RuntimeException('Configure a chave da API Gemini em Ads Intelligence → Settings.');
        }

        $message = $this->getMessageRepository()->find($messageId);
        if (!$message instanceof AdsCreativeMessage) {
            throw new \RuntimeException('Mensagem não encontrada.');
        }

        $conversation = $this->requireConversation($userId, $message->getConversationId());

        if ('user' === $message->getRole()) {
            $userMessage = $message;
            $assistant   = $this->findFollowingAssistant($conversation, $userMessage);
        } else {
            $assistant   = $message;
            $userMessage = $this->findPrecedingUserMessage($conversation, $assistant);
            if (!$userMessage instanceof AdsCreativeMessage) {
                throw new \RuntimeException('Prompt original não encontrado para regenerar.');
            }
        }

        $prompt = trim((string) $userMessage->getContent());
        if ('' === $prompt) {
            throw new \InvalidArgumentException('O prompt original está vazio.');
        }

        $userMeta      = $userMessage->getMeta() ?? [];
        $assistantMeta = $assistant?->getMeta() ?? [];
        $mode          = (string) ($userMeta['mode'] ?? $assistantMeta['mode'] ?? $conversation->getMode());
        $model         = (string) ($userMeta['model'] ?? $assistantMeta['model'] ?? $conversation->getModel());
        $aspectRatio   = (string) ($userMeta['aspect_ratio'] ?? $assistantMeta['aspect_ratio'] ?? '16:9');
        $extendVideo   = (bool) ($userMeta['extend_video'] ?? $assistantMeta['extend_video'] ?? false);

        if (!CreativeModelCatalog::isValidMode($mode)) {
            throw new \InvalidArgumentException('Modo inválido.');
        }
        if (!CreativeModelCatalog::isValidModel($mode, $model)) {
            throw new \InvalidArgumentException('Modelo inválido para o modo selecionado.');
        }

        $conversation->setMode($mode)->setModel($model)->touch();
        $this->entityManager->persist($conversation);

        $referenceInline = null;
        if ('image' === $userMessage->getMediaType()) {
            $absolute = $this->mediaService->resolveAbsolutePath($userMessage);
            if (null !== $absolute) {
                $referenceInline = $this->mediaService->toInlineData($absolute);
            }
        }

        return match ($mode) {
            CreativeModelCatalog::MODE_TEXT  => $this->handleText($conversation, $userMessage, $model, $prompt, $assistant),
            CreativeModelCatalog::MODE_IMAGE => $this->handleImage($conversation, $userMessage, $model, $prompt, $assistant),
            CreativeModelCatalog::MODE_VIDEO => $this->handleVideo(
                $conversation,
                $userMessage,
                $model,
                $prompt,
                $extendVideo,
                $referenceInline,
                $aspectRatio,
                $assistant
            ),
            default => throw new \InvalidArgumentException('Modo inválido.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function pollMessage(int $userId, int $messageId): array
    {
        $message = $this->getMessageRepository()->find($messageId);
        if (!$message instanceof AdsCreativeMessage) {
            throw new \RuntimeException('Mensagem não encontrada.');
        }

        $conversation = $this->requireConversation($userId, $message->getConversationId());

        if (AdsCreativeMessage::STATUS_COMPLETED === $message->getStatus()
            || AdsCreativeMessage::STATUS_FAILED === $message->getStatus()
        ) {
            return [
                'done'         => true,
                'conversation' => $this->serializeConversation($conversation),
                'message'      => $this->serializeMessage($message),
                'canExtend'    => null !== $this->getMessageRepository()->findLastAssistantWithVideo((int) $conversation->getId()),
            ];
        }

        $operation = $message->getGeminiOperation();
        if (null === $operation || '' === $operation) {
            $message->setStatus(AdsCreativeMessage::STATUS_FAILED)
                ->setContent('Operação de vídeo ausente.');
            $this->persistGenerationState($conversation, $message);

            return [
                'done'         => true,
                'conversation' => $this->serializeConversation($conversation),
                'message'      => $this->serializeMessage($message),
                'canExtend'    => false,
            ];
        }

        $result = $this->geminiClient->pollVideoOperation($operation);
        if (!$result['done']) {
            return [
                'done'         => false,
                'conversation' => $this->serializeConversation($conversation),
                'message'      => $this->serializeMessage($message),
                'canExtend'    => false,
            ];
        }

        if (null !== $result['error']) {
            if ($this->shouldRetryAudioFilter($message, $result['error'])) {
                $retried = $this->retryVideoAfterAudioFilter($conversation, $message);
                if (null !== $retried) {
                    return $retried;
                }
            }

            $message->setStatus(AdsCreativeMessage::STATUS_FAILED)
                ->setContent($this->formatVideoFailureContent($result['error']));
            $conversation->touch();
            $this->persistGenerationState($conversation, $message);

            return [
                'done'         => true,
                'conversation' => $this->serializeConversation($conversation),
                'message'      => $this->serializeMessage($message),
                'canExtend'    => false,
            ];
        }

        $stored = $this->mediaService->storeBinary(
            (int) $conversation->getId(),
            (string) $result['videoBytes'],
            'mp4',
            'video/mp4'
        );

        $message
            ->setStatus(AdsCreativeMessage::STATUS_COMPLETED)
            ->setContent('Vídeo gerado com sucesso.')
            ->setMediaType('video')
            ->setMediaPath($stored['relativePath'])
            ->setMimeType($stored['mimeType'])
            ->setFileSize($stored['fileSize'])
            ->setGeminiVideoUri($result['videoUri'])
            ->setGeminiVideoName($result['videoName']);

        $conversation->touch();
        $this->persistGenerationState($conversation, $message);

        $this->logger->info('AdsIntelligence Creative: video generation persisted.', [
            'conversation_id' => $conversation->getId(),
            'message_id'      => $message->getId(),
            'operation'       => $operation,
            'media_path'      => $stored['relativePath'],
            'mime_type'       => $stored['mimeType'],
            'file_size'       => $stored['fileSize'],
        ]);

        return [
            'done'         => true,
            'conversation' => $this->serializeConversation($conversation),
            'message'      => $this->serializeMessage($message),
            'canExtend'    => true,
        ];
    }

    /**
     * @return array{assetId: int, assetUrl: string, message: array<string, mixed>}
     */
    public function saveMessageAsset(int $userId, int $messageId): array
    {
        $message = $this->getMessageRepository()->find($messageId);
        if (!$message instanceof AdsCreativeMessage) {
            throw new \RuntimeException('Mensagem não encontrada.');
        }

        $this->requireConversation($userId, $message->getConversationId());

        if (!$message->hasMedia()) {
            throw new \RuntimeException('Esta mensagem não possui mídia para salvar.');
        }

        if (null !== $message->getAssetId() && null !== $message->getAssetUrl()) {
            return [
                'assetId'  => (int) $message->getAssetId(),
                'assetUrl' => (string) $message->getAssetUrl(),
                'message'  => $this->serializeMessage($message),
            ];
        }

        $title = sprintf(
            'Ads Creative %s #%d',
            $message->getMediaType(),
            (int) $message->getId()
        );

        $saved = $this->mediaService->saveMessageToAsset($message, $title);
        $message->setAssetId($saved['assetId'])->setAssetUrl($saved['assetUrl']);
        $this->entityManager->flush();

        return [
            'assetId'  => $saved['assetId'],
            'assetUrl' => $saved['assetUrl'],
            'message'  => $this->serializeMessage($message),
        ];
    }

    public function getDownloadableMessage(int $userId, int $messageId): AdsCreativeMessage
    {
        $message = $this->getMessageRepository()->find($messageId);
        if (!$message instanceof AdsCreativeMessage) {
            throw new \RuntimeException('Mensagem não encontrada.');
        }

        $this->requireConversation($userId, $message->getConversationId());

        if (!$message->hasMedia()) {
            throw new \RuntimeException('Mensagem sem mídia para download.');
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    private function handleText(
        AdsCreativeConversation $conversation,
        AdsCreativeMessage $userMessage,
        string $model,
        string $prompt,
        ?AdsCreativeMessage $existingAssistant = null,
    ): array {
        $assistant = $this->prepareAssistantForGeneration($conversation, $existingAssistant);

        try {
            $result = $this->geminiClient->generateText($model, $prompt);
            $assistant
                ->setContent($result['text'])
                ->setMediaType('none')
                ->setStatus(AdsCreativeMessage::STATUS_COMPLETED)
                ->setMeta(['mode' => CreativeModelCatalog::MODE_TEXT, 'model' => $model]);
        } catch (\Throwable $e) {
            $assistant
                ->setContent($e->getMessage())
                ->setMediaType('none')
                ->setStatus(AdsCreativeMessage::STATUS_FAILED)
                ->setMeta(['mode' => CreativeModelCatalog::MODE_TEXT, 'model' => $model, 'error' => true]);
        }

        $this->entityManager->persist($assistant);
        $this->entityManager->flush();

        return [
            'pending'      => false,
            'conversation' => $this->serializeConversation($conversation),
            'userMessage'  => $this->serializeMessage($userMessage),
            'message'      => $this->serializeMessage($assistant),
            'canExtend'    => false,
            'replayed'     => null !== $existingAssistant,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleImage(
        AdsCreativeConversation $conversation,
        AdsCreativeMessage $userMessage,
        string $model,
        string $prompt,
        ?AdsCreativeMessage $existingAssistant = null,
    ): array {
        $assistant = $this->prepareAssistantForGeneration($conversation, $existingAssistant);

        try {
            $result = $this->geminiClient->generateImage($model, $prompt);
            if (null === $result['imageBytes'] || '' === $result['imageBytes']) {
                throw new \RuntimeException('Nenhuma imagem foi gerada.');
            }

            $mime   = $result['mimeType'] ?: 'image/png';
            $stored = $this->mediaService->storeBinary(
                (int) $conversation->getId(),
                $result['imageBytes'],
                $this->mediaService->extensionFromMime($mime),
                $mime
            );

            $assistant
                ->setContent($result['text'] !== '' ? $result['text'] : 'Imagem gerada com sucesso.')
                ->setMediaType('image')
                ->setMediaPath($stored['relativePath'])
                ->setMimeType($stored['mimeType'])
                ->setFileSize($stored['fileSize'])
                ->setStatus(AdsCreativeMessage::STATUS_COMPLETED)
                ->setMeta(['mode' => CreativeModelCatalog::MODE_IMAGE, 'model' => $model]);
        } catch (\Throwable $e) {
            $assistant
                ->setContent($e->getMessage())
                ->setMediaType('none')
                ->setStatus(AdsCreativeMessage::STATUS_FAILED)
                ->setMeta(['mode' => CreativeModelCatalog::MODE_IMAGE, 'model' => $model, 'error' => true]);
        }

        $this->entityManager->persist($assistant);
        $this->entityManager->flush();

        return [
            'pending'      => false,
            'conversation' => $this->serializeConversation($conversation),
            'userMessage'  => $this->serializeMessage($userMessage),
            'message'      => $this->serializeMessage($assistant),
            'canExtend'    => false,
            'replayed'     => null !== $existingAssistant,
        ];
    }

    /**
     * @param array{mimeType: string, data: string}|null $referenceInline
     *
     * @return array<string, mixed>
     */
    private function handleVideo(
        AdsCreativeConversation $conversation,
        AdsCreativeMessage $userMessage,
        string $model,
        string $prompt,
        bool $extendVideo,
        ?array $referenceInline,
        string $aspectRatio,
        ?AdsCreativeMessage $existingAssistant = null,
    ): array {
        $videoToExtend = null;
        $excludeId     = $existingAssistant?->getId();

        if ($extendVideo) {
            if (!CreativeModelCatalog::supportsVideoExtend($model)) {
                throw new \InvalidArgumentException('Extensão de vídeo requer o modelo Veo 3.1 (não Fast).');
            }

            $previous = $this->getMessageRepository()->findLastAssistantWithVideo((int) $conversation->getId());
            if ($previous instanceof AdsCreativeMessage && null !== $excludeId && $previous->getId() === $excludeId) {
                $previous = $this->findPreviousAssistantWithVideo($conversation, $excludeId);
            }
            if (!$previous instanceof AdsCreativeMessage) {
                throw new \RuntimeException('Não há vídeo anterior nesta conversa para estender.');
            }

            $videoUri = $previous->getGeminiVideoUri();
            if (is_string($videoUri) && '' !== $videoUri) {
                $videoToExtend = ['uri' => $videoUri];
            } else {
                $absolute = $this->mediaService->resolveAbsolutePath($previous);
                if (null === $absolute) {
                    throw new \RuntimeException('Arquivo/URI do vídeo anterior não foi encontrado para extensão.');
                }

                $inline = $this->mediaService->toInlineData($absolute);
                $videoToExtend = [
                    'mimeType' => $inline['mimeType'],
                    'data'     => $inline['data'],
                ];
            }
        }

        $assistant = $this->prepareAssistantForGeneration($conversation, $existingAssistant);

        try {
            $started = $this->geminiClient->startVideoGeneration(
                $model,
                $prompt,
                $extendVideo ? null : $referenceInline,
                $videoToExtend,
                $aspectRatio
            );

            $assistant
                ->setContent('Gerando vídeo com áudio nativo… isso pode levar 1–3 minutos.')
                ->setMediaType('video')
                ->setStatus(AdsCreativeMessage::STATUS_PENDING)
                ->setGeminiOperation($started['operationName'])
                ->setMeta([
                    'mode'          => CreativeModelCatalog::MODE_VIDEO,
                    'model'         => $model,
                    'extend_video'  => $extendVideo,
                    'aspect_ratio'  => $aspectRatio,
                    'prompt'        => $prompt,
                    'has_reference' => null !== $referenceInline,
                    'audio_retry'   => 0,
                ]);
        } catch (\Throwable $e) {
            $assistant
                ->setContent($e->getMessage())
                ->setMediaType('none')
                ->setStatus(AdsCreativeMessage::STATUS_FAILED)
                ->setMeta(['mode' => CreativeModelCatalog::MODE_VIDEO, 'model' => $model, 'error' => true]);

            $this->entityManager->persist($assistant);
            $this->entityManager->flush();

            return [
                'pending'      => false,
                'conversation' => $this->serializeConversation($conversation),
                'userMessage'  => $this->serializeMessage($userMessage),
                'message'      => $this->serializeMessage($assistant),
                'canExtend'    => false,
                'replayed'     => null !== $existingAssistant,
            ];
        }

        $this->entityManager->persist($assistant);
        $this->entityManager->flush();

        return [
            'pending'      => true,
            'conversation' => $this->serializeConversation($conversation),
            'userMessage'  => $this->serializeMessage($userMessage),
            'message'      => $this->serializeMessage($assistant),
            'canExtend'    => false,
            'replayed'     => null !== $existingAssistant,
        ];
    }

    private function prepareAssistantForGeneration(
        AdsCreativeConversation $conversation,
        ?AdsCreativeMessage $existingAssistant,
    ): AdsCreativeMessage {
        if ($existingAssistant instanceof AdsCreativeMessage) {
            $absolute = $this->mediaService->resolveAbsolutePath($existingAssistant);
            if (null !== $absolute && is_file($absolute)) {
                @unlink($absolute);
            }

            return $existingAssistant
                ->setContent(null)
                ->setMediaType('none')
                ->setMediaPath(null)
                ->setMimeType(null)
                ->setFileSize(null)
                ->setGeminiOperation(null)
                ->setGeminiVideoName(null)
                ->setGeminiVideoUri(null)
                ->setAssetId(null)
                ->setAssetUrl(null)
                ->setStatus(AdsCreativeMessage::STATUS_PENDING)
                ->setMeta(null);
        }

        return (new AdsCreativeMessage())
            ->setConversationId((int) $conversation->getId())
            ->setRole('assistant')
            ->setMediaType('none')
            ->setStatus(AdsCreativeMessage::STATUS_PENDING);
    }

    private function findFollowingAssistant(
        AdsCreativeConversation $conversation,
        AdsCreativeMessage $userMessage,
    ): ?AdsCreativeMessage {
        $foundUser = false;

        foreach ($this->getMessageRepository()->findByConversation((int) $conversation->getId()) as $item) {
            if (!$foundUser) {
                if ($item->getId() === $userMessage->getId()) {
                    $foundUser = true;
                }
                continue;
            }

            if ('assistant' === $item->getRole()) {
                return $item;
            }

            if ('user' === $item->getRole()) {
                return null;
            }
        }

        return null;
    }

    private function findPreviousAssistantWithVideo(
        AdsCreativeConversation $conversation,
        int $excludeMessageId,
    ): ?AdsCreativeMessage {
        $previous = null;

        foreach ($this->getMessageRepository()->findByConversation((int) $conversation->getId()) as $item) {
            if (
                'assistant' === $item->getRole()
                && 'video' === $item->getMediaType()
                && AdsCreativeMessage::STATUS_COMPLETED === $item->getStatus()
                && $item->hasMedia()
                && $item->getId() !== $excludeMessageId
            ) {
                $previous = $item;
            }
        }

        return $previous;
    }

    private function requireConversation(int $userId, int $conversationId): AdsCreativeConversation
    {
        $conversation = $this->getConversationRepository()->findOneByIdAndUser($conversationId, $userId);
        if (!$conversation instanceof AdsCreativeConversation) {
            throw new \RuntimeException('Conversa não encontrada.');
        }

        return $conversation;
    }

    private function shouldRetryAudioFilter(AdsCreativeMessage $message, string $error): bool
    {
        $meta = $message->getMeta() ?? [];
        $retries = (int) ($meta['audio_retry'] ?? 0);
        if ($retries >= 1) {
            return false;
        }

        $normalized = strtolower($error);

        return str_contains($normalized, 'audio')
            || str_contains($normalized, 'safety')
            || str_contains($normalized, 'filtro de segurança')
            || str_contains($normalized, 'rai');
    }

    private function formatVideoFailureContent(string $error): string
    {
        $normalized = strtolower($error);
        $isAudioFilter = str_contains($normalized, 'audio')
            || (str_contains($normalized, 'safety') && str_contains($normalized, 'audio'));

        if ($isAudioFilter) {
            return $error."\n\n"
                .'Dica: o Veo bloqueou o áudio desta tentativa. Use Replay com um prompt sem fala '
                .'(só movimento/ambiente) ou coloque o diálogo entre aspas, ex.: dizendo "Let\'s Work".';
        }

        return $error;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function retryVideoAfterAudioFilter(
        AdsCreativeConversation $conversation,
        AdsCreativeMessage $message,
    ): ?array {
        $meta         = $message->getMeta() ?? [];
        $prompt       = trim((string) ($meta['prompt'] ?? $meta['enhanced_prompt'] ?? ''));
        $model        = (string) ($meta['model'] ?? $conversation->getModel());
        $aspectRatio  = (string) ($meta['aspect_ratio'] ?? '16:9');
        $extendVideo  = (bool) ($meta['extend_video'] ?? false);
        $precedingUser = $this->findPrecedingUserMessage($conversation, $message);

        if ('' === $prompt && $precedingUser instanceof AdsCreativeMessage) {
            $prompt = trim((string) $precedingUser->getContent());
        }

        if ('' === $prompt) {
            $this->logger->warning('AdsIntelligence Creative: audio retry skipped — prompt missing on message '.$message->getId());

            return null;
        }

        $referenceInline = null;
        if (!$extendVideo) {
            $referenceInline = $this->resolveReferenceInlineForRetry($conversation, $precedingUser);
        }

        $videoToExtend = null;
        if ($extendVideo) {
            $previous = $this->getMessageRepository()->findLastAssistantWithVideo((int) $conversation->getId());
            if ($previous instanceof AdsCreativeMessage && $previous->getId() !== $message->getId()) {
                $uri = $previous->getGeminiVideoUri();
                if (is_string($uri) && '' !== $uri) {
                    $videoToExtend = ['uri' => $uri];
                }
            }
        }

        try {
            $started = $this->geminiClient->startVideoGeneration(
                $model,
                $prompt,
                $extendVideo ? null : $referenceInline,
                $videoToExtend,
                $aspectRatio,
                true
            );
        } catch (\Throwable $e) {
            $this->logger->warning('AdsIntelligence Creative: audio retry start failed: '.$e->getMessage());

            return null;
        }

        $meta['prompt']         = $prompt;
        $meta['has_reference']  = null !== $referenceInline;
        $meta['audio_retry']    = ((int) ($meta['audio_retry'] ?? 0)) + 1;
        $meta['retry_reason']   = 'audio_safety_filter';

        $message
            ->setStatus(AdsCreativeMessage::STATUS_PENDING)
            ->setContent('Filtro de áudio do Veo bloqueou a 1ª tentativa. Gerando novamente só com áudio ambiente (sem fala)…')
            ->setGeminiOperation($started['operationName'])
            ->setMeta($meta);

        $conversation->touch();
        $this->persistGenerationState($conversation, $message);

        return [
            'done'         => false,
            'conversation' => $this->serializeConversation($conversation),
            'message'      => $this->serializeMessage($message),
            'canExtend'    => false,
        ];
    }

    private function persistGenerationState(
        AdsCreativeConversation $conversation,
        AdsCreativeMessage $message,
    ): void {
        // Long-running SSE requests can cross Doctrine's automatic dirty-check window.
        // Re-persisting managed entities explicitly schedules their final state for update.
        $this->entityManager->persist($conversation);
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    private function findPrecedingUserMessage(
        AdsCreativeConversation $conversation,
        AdsCreativeMessage $assistantMessage,
    ): ?AdsCreativeMessage {
        $messages = $this->getMessageRepository()->findByConversation((int) $conversation->getId());
        $previous = null;

        foreach ($messages as $item) {
            if ($item->getId() === $assistantMessage->getId()) {
                break;
            }
            if ('user' === $item->getRole()) {
                $previous = $item;
            }
        }

        return $previous;
    }

    /**
     * @return array{mimeType: string, data: string}|null
     */
    private function resolveReferenceInlineForRetry(
        AdsCreativeConversation $conversation,
        ?AdsCreativeMessage $precedingUser,
    ): ?array {
        $candidates = [];

        if ($precedingUser instanceof AdsCreativeMessage && 'image' === $precedingUser->getMediaType()) {
            $candidates[] = $precedingUser;
        }

        foreach (array_reverse($this->getMessageRepository()->findByConversation((int) $conversation->getId())) as $userMessage) {
            if ('user' === $userMessage->getRole() && 'image' === $userMessage->getMediaType()) {
                $candidates[] = $userMessage;
                break;
            }
        }

        foreach ($candidates as $candidate) {
            $absolute = $this->mediaService->resolveAbsolutePath($candidate);
            if (null === $absolute) {
                continue;
            }

            return $this->mediaService->toInlineData($absolute);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConversation(AdsCreativeConversation $conversation): array
    {
        return [
            'id'           => $conversation->getId(),
            'title'        => $conversation->getTitle(),
            'mode'         => $conversation->getMode(),
            'model'        => $conversation->getModel(),
            'dateAdded'    => $conversation->getDateAdded()->format(DATE_ATOM),
            'dateModified' => $conversation->getDateModified()?->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(AdsCreativeMessage $message): array
    {
        return [
            'id'               => $message->getId(),
            'conversationId'   => $message->getConversationId(),
            'role'             => $message->getRole(),
            'content'          => $message->getContent(),
            'mediaType'        => $message->getMediaType(),
            'mimeType'         => $message->getMimeType(),
            'fileSize'         => $message->getFileSize(),
            'hasMedia'         => $message->hasMedia() && AdsCreativeMessage::STATUS_COMPLETED === $message->getStatus(),
            'geminiOperation'  => $message->getGeminiOperation(),
            'geminiVideoName'  => $message->getGeminiVideoName(),
            'assetId'          => $message->getAssetId(),
            'assetUrl'         => $message->getAssetUrl(),
            'status'           => $message->getStatus(),
            'meta'             => $message->getMeta(),
            'dateAdded'        => $message->getDateAdded()->format(DATE_ATOM),
            'previewUrl'       => $message->hasMedia() && AdsCreativeMessage::STATUS_COMPLETED === $message->getStatus()
                ? null // filled by controller with routed URL
                : null,
        ];
    }

    private function getConversationRepository(): AdsCreativeConversationRepository
    {
        return $this->entityManager->getRepository(AdsCreativeConversation::class);
    }

    private function getMessageRepository(): AdsCreativeMessageRepository
    {
        return $this->entityManager->getRepository(AdsCreativeMessage::class);
    }
}
