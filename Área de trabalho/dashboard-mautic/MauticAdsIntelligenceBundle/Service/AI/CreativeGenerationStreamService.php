<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreativeGenerationStreamService
{
    public const EVENT_STARTED   = 'started';
    public const EVENT_PROGRESS  = 'progress';
    public const EVENT_COMPLETED = 'completed';
    public const EVENT_FAILED    = 'failed';
    public const EVENT_ERROR     = 'error';

    public function __construct(
        private readonly CreativeConversationService $conversationService,
        private readonly int $pollIntervalSeconds = 5,
        private readonly int $timeoutSeconds = 300,
    ) {
    }

    /**
     * @param callable(string, array<string, mixed>): void $emit
     * @param callable(): bool                             $isDisconnected
     */
    public function streamMessage(
        int $userId,
        int $conversationId,
        string $prompt,
        string $mode,
        string $model,
        bool $extendVideo,
        ?UploadedFile $referenceImage,
        string $aspectRatio,
        callable $emit,
        callable $isDisconnected,
    ): void {
        $emit(self::EVENT_STARTED, ['pending' => true]);

        // Always finish starting the generation so a client disconnect cannot
        // leave a submitted prompt without its persisted assistant response.
        $result = $this->conversationService->sendMessage(
            $userId,
            $conversationId,
            $prompt,
            $mode,
            $model,
            $extendVideo,
            $referenceImage,
            $aspectRatio,
        );

        $this->emitAndAwaitResult($userId, $result, $emit, $isDisconnected);
    }

    /**
     * @param callable(string, array<string, mixed>): void $emit
     * @param callable(): bool                             $isDisconnected
     */
    public function streamReplay(
        int $userId,
        int $messageId,
        callable $emit,
        callable $isDisconnected,
    ): void {
        $emit(self::EVENT_STARTED, ['pending' => true, 'replayed' => true]);

        $result = $this->conversationService->replayMessage($userId, $messageId);
        unset($result['userMessage']);

        $this->emitAndAwaitResult($userId, $result, $emit, $isDisconnected);
    }

    /**
     * Resumes a pending generation after the browser has navigated away or reloaded.
     *
     * @param callable(string, array<string, mixed>): void $emit
     * @param callable(): bool                             $isDisconnected
     */
    public function streamPendingMessage(
        int $userId,
        int $messageId,
        callable $emit,
        callable $isDisconnected,
    ): void {
        $emit(self::EVENT_STARTED, ['pending' => true, 'resumed' => true]);

        $result = $this->conversationService->pollMessage($userId, $messageId);
        $this->emitAndAwaitResult($userId, $result, $emit, $isDisconnected);
    }

    /**
     * @param array<string, mixed>                         $result
     * @param callable(string, array<string, mixed>): void $emit
     * @param callable(): bool                             $isDisconnected
     */
    private function emitAndAwaitResult(
        int $userId,
        array $result,
        callable $emit,
        callable $isDisconnected,
    ): void {
        $operationStartedAt = microtime(true);
        $activeOperation    = null;

        while (true) {
            $operation = (string) ($result['message']['geminiOperation'] ?? '');
            if ('' !== $operation && $operation !== $activeOperation) {
                $activeOperation    = $operation;
                $operationStartedAt = microtime(true);
            }

            $pending = $this->isPending($result);
            $emit($pending ? self::EVENT_PROGRESS : $this->terminalEvent($result), $result);

            if (!$pending || $isDisconnected()) {
                return;
            }

            if (microtime(true) - $operationStartedAt >= $this->timeoutSeconds) {
                throw new \RuntimeException('A geração excedeu o tempo limite. Reabra a conversa para continuar acompanhando.');
            }

            if ($this->pollIntervalSeconds > 0) {
                sleep($this->pollIntervalSeconds);
            }

            $messageId = (int) ($result['message']['id'] ?? 0);
            if ($messageId <= 0) {
                throw new \RuntimeException('A geração não retornou uma mensagem válida para acompanhamento.');
            }

            $result = $this->conversationService->pollMessage($userId, $messageId);
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function isPending(array $result): bool
    {
        if (array_key_exists('pending', $result)) {
            return true === $result['pending'];
        }

        return array_key_exists('done', $result) && false === $result['done'];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function terminalEvent(array $result): string
    {
        return 'failed' === ($result['message']['status'] ?? null)
            ? self::EVENT_FAILED
            : self::EVENT_COMPLETED;
    }
}
