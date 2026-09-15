<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeConversationService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeGenerationStreamService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeMediaService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeModelCatalog;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\GeminiSettingsService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\MigrationRunner;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreativeStudioController extends CommonController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'mautic.ads_intelligence.service.creative_conversation'      => CreativeConversationService::class,
            'mautic.ads_intelligence.service.creative_generation_stream' => CreativeGenerationStreamService::class,
            'mautic.ads_intelligence.service.creative_media'             => CreativeMediaService::class,
            'mautic.ads_intelligence.service.gemini_settings'            => GeminiSettingsService::class,
            'mautic.ads_intelligence.service.migration_runner'           => MigrationRunner::class,
        ]);
    }

    public function indexAction(Request $request): Response
    {
        $this->assertCanViewCreatives();
        $this->ensureSchema();

        return $this->delegateView([
            'viewParameters' => [
                'currentRoute'   => 'mautic_ads_intelligence_creatives',
                'geminiSettings' => $this->getGeminiSettings()->getSettings(),
                'modelsByMode'   => CreativeModelCatalog::getModelsByMode(),
            ],
            'contentTemplate' => '@MauticAdsIntelligence/CreativeStudio/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_creatives',
                'mauticContent' => 'adsIntelligenceCreatives',
            ],
        ]);
    }

    public function listConversationsAction(Request $request): JsonResponse
    {
        $this->assertCanViewCreatives();
        $this->ensureSchema();

        return $this->json([
            'success'       => true,
            'conversations' => $this->getConversationService()->listConversations($this->getCurrentUserId()),
        ]);
    }

    public function createConversationAction(Request $request): JsonResponse
    {
        $this->assertCanViewCreatives();

        try {
            $this->ensureSchema();
            $this->assertCsrf($request);

            $payload = $this->decodeJson($request);
            $mode    = (string) ($payload['mode'] ?? CreativeModelCatalog::MODE_TEXT);
            $model   = isset($payload['model']) ? (string) $payload['model'] : null;

            $conversation = $this->getConversationService()->createConversation(
                $this->getCurrentUserId(),
                $mode,
                $model
            );

            return $this->json(['success' => true, 'conversation' => $conversation]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getConversationAction(Request $request, int $id): JsonResponse
    {
        $this->assertCanViewCreatives();

        try {
            $data             = $this->getConversationService()->getConversation($this->getCurrentUserId(), $id);
            $data['messages'] = array_map(
                fn (array $message) => $this->enrichMessageUrls($message),
                $data['messages']
            );

            return $this->json(['success' => true] + $data);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function deleteConversationAction(Request $request, int $id): JsonResponse
    {
        $this->assertCanViewCreatives();

        try {
            $this->assertCsrf($request);
            $this->getConversationService()->deleteConversation($this->getCurrentUserId(), $id);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function sendMessageAction(Request $request, int $id): JsonResponse
    {
        $this->assertCanViewCreatives();

        try {
            $this->assertCsrf($request);
            $options = $this->getGenerationOptions($request);

            $result = $this->getConversationService()->sendMessage(
                $this->getCurrentUserId(),
                $id,
                $options['prompt'],
                $options['mode'],
                $options['model'],
                $options['extendVideo'],
                $options['reference'],
                $options['aspectRatio']
            );

            if (isset($result['userMessage']) && is_array($result['userMessage'])) {
                $result['userMessage'] = $this->enrichMessageUrls($result['userMessage']);
            }
            if (isset($result['message']) && is_array($result['message'])) {
                $result['message'] = $this->enrichMessageUrls($result['message']);
            }

            return $this->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function streamMessageAction(Request $request, int $id): Response
    {
        $this->assertCanViewCreatives();

        try {
            $this->assertCsrf($request);
            $options = $this->getGenerationOptions($request);
            $userId  = $this->getCurrentUserId();
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->releaseSession($request);

        return $this->createEventStream(function (callable $emit, callable $isDisconnected) use ($userId, $id, $options): void {
            $this->getGenerationStreamService()->streamMessage(
                $userId,
                $id,
                $options['prompt'],
                $options['mode'],
                $options['model'],
                $options['extendVideo'],
                $options['reference'],
                $options['aspectRatio'],
                $emit,
                $isDisconnected,
            );
        });
    }

    public function messageStatusAction(Request $request, int $id): JsonResponse
    {
        $this->assertCanViewCreatives();

        try {
            $result = $this->getConversationService()->pollMessage($this->getCurrentUserId(), $id);
            if (isset($result['message']) && is_array($result['message'])) {
                $result['message'] = $this->enrichMessageUrls($result['message']);
            }

            return $this->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function streamPendingMessageAction(Request $request, int $id): Response
    {
        $this->assertCanViewCreatives();

        try {
            // This GET can finalize and persist an asynchronous video operation.
            $this->assertCsrf($request);
            $userId = $this->getCurrentUserId();
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->releaseSession($request);

        return $this->createEventStream(function (callable $emit, callable $isDisconnected) use ($userId, $id): void {
            $this->getGenerationStreamService()->streamPendingMessage(
                $userId,
                $id,
                $emit,
                $isDisconnected,
            );
        });
    }

    public function replayMessageAction(Request $request, int $id): JsonResponse
    {
        $this->assertCanViewCreatives();

        try {
            $this->assertCsrf($request);
            $result = $this->getConversationService()->replayMessage($this->getCurrentUserId(), $id);

            if (isset($result['message']) && is_array($result['message'])) {
                $result['message'] = $this->enrichMessageUrls($result['message']);
            }

            // Replay must not append a new user bubble in the UI.
            unset($result['userMessage']);

            return $this->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function streamReplayMessageAction(Request $request, int $id): Response
    {
        $this->assertCanViewCreatives();

        try {
            $this->assertCsrf($request);
            $userId = $this->getCurrentUserId();
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->releaseSession($request);

        return $this->createEventStream(function (callable $emit, callable $isDisconnected) use ($userId, $id): void {
            $this->getGenerationStreamService()->streamReplay(
                $userId,
                $id,
                $emit,
                $isDisconnected,
            );
        });
    }

    public function downloadMessageAction(Request $request, int $id): Response
    {
        $this->assertCanViewCreatives();

        try {
            $message  = $this->getConversationService()->getDownloadableMessage($this->getCurrentUserId(), $id);
            $absolute = $this->getMediaService()->resolveAbsolutePath($message);
            if (null === $absolute) {
                throw new \RuntimeException('Arquivo não encontrado.');
            }

            $response = new BinaryFileResponse($absolute);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($absolute)
            );
            $response->headers->set('Content-Type', $message->getMimeType() ?: 'application/octet-stream');

            return $response;
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function previewMessageAction(Request $request, int $id): Response
    {
        $this->assertCanViewCreatives();

        try {
            $message  = $this->getConversationService()->getDownloadableMessage($this->getCurrentUserId(), $id);
            $absolute = $this->getMediaService()->resolveAbsolutePath($message);
            if (null === $absolute) {
                throw new \RuntimeException('Arquivo não encontrado.');
            }

            $response = new BinaryFileResponse($absolute);
            $response->headers->set('Content-Type', $message->getMimeType() ?: 'application/octet-stream');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($absolute));

            return $response;
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function saveAssetAction(Request $request, int $id): JsonResponse
    {
        $this->assertCanViewCreatives();

        try {
            $this->assertCsrf($request);
            $result            = $this->getConversationService()->saveMessageAsset($this->getCurrentUserId(), $id);
            $result['message'] = $this->enrichMessageUrls($result['message']);

            return $this->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    private function enrichMessageUrls(array $message): array
    {
        $id = (int) ($message['id'] ?? 0);
        if ($id > 0 && !empty($message['hasMedia'])) {
            $message['previewUrl']   = $this->generateUrl('mautic_ads_intelligence_creatives_message_preview', ['id' => $id]);
            $message['downloadUrl']  = $this->generateUrl('mautic_ads_intelligence_creatives_message_download', ['id' => $id]);
            $message['saveAssetUrl'] = $this->generateUrl('mautic_ads_intelligence_creatives_message_save_asset', ['id' => $id]);
        }

        if ($id > 0) {
            $message['statusUrl'] = $this->generateUrl('mautic_ads_intelligence_creatives_message_status', ['id' => $id]);
            $message['streamUrl'] = $this->generateUrl('mautic_ads_intelligence_creatives_message_stream', ['id' => $id]);
        }

        return $message;
    }

    /**
     * @param callable(callable(string, array<string, mixed>): void, callable(): bool): void $producer
     */
    private function createEventStream(callable $producer): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($producer): void {
            @set_time_limit(0);
            echo "retry: 3000\n\n";
            $this->flushStreamOutput();

            $emit = function (string $event, array $data): void {
                $payload = $this->enrichGenerationPayload($data);
                $json    = json_encode(
                    ['success' => true] + $payload,
                    JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE,
                );

                echo $this->formatServerEvent($event, $json);
                $this->flushStreamOutput();
            };

            try {
                $producer($emit, static fn (): bool => 0 !== connection_aborted());
            } catch (\Throwable $e) {
                $json = json_encode(
                    ['success' => false, 'error' => $e->getMessage()],
                    JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE,
                );
                echo $this->formatServerEvent(CreativeGenerationStreamService::EVENT_ERROR, $json);
                $this->flushStreamOutput();
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    private function formatServerEvent(string $event, string $data): string
    {
        if (1 !== preg_match('/^[A-Za-z0-9_-]+$/D', $event)) {
            throw new \InvalidArgumentException('Invalid SSE event name.');
        }

        $lines = preg_split('/\r\n|\r|\n/', $data) ?: [''];

        return 'event: '.$event."\n"
            .implode("\n", array_map(static fn (string $line): string => 'data: '.$line, $lines))
            ."\n\n";
    }

    private function flushStreamOutput(): void
    {
        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function enrichGenerationPayload(array $payload): array
    {
        if (isset($payload['userMessage']) && is_array($payload['userMessage'])) {
            $payload['userMessage'] = $this->enrichMessageUrls($payload['userMessage']);
        }
        if (isset($payload['message']) && is_array($payload['message'])) {
            $payload['message'] = $this->enrichMessageUrls($payload['message']);
        }

        return $payload;
    }

    /**
     * @return array{
     *     prompt: string,
     *     mode: string,
     *     model: string,
     *     extendVideo: bool,
     *     reference: UploadedFile|null,
     *     aspectRatio: string
     * }
     */
    private function getGenerationOptions(Request $request): array
    {
        $mode      = (string) $request->request->get('mode', CreativeModelCatalog::MODE_TEXT);
        $reference = $request->files->get('reference_image');

        return [
            'prompt'       => trim((string) $request->request->get('prompt', '')),
            'mode'         => $mode,
            'model'        => (string) $request->request->get('model', CreativeModelCatalog::defaultModel($mode)),
            'extendVideo'  => filter_var($request->request->get('extend_video', false), FILTER_VALIDATE_BOOLEAN),
            'reference'    => $reference instanceof UploadedFile ? $reference : null,
            'aspectRatio'  => (string) $request->request->get('aspect_ratio', '16:9'),
        ];
    }

    private function releaseSession(Request $request): void
    {
        if ($request->hasSession() && $request->getSession()->isStarted()) {
            $request->getSession()->save();
        }
    }

    private function assertCsrf(Request $request): void
    {
        $token = (string) (
            $request->headers->get('X-CSRF-Token')
            ?? $request->request->get('_token')
            ?? ''
        );

        if (!$this->isCsrfTokenValid('ads_intelligence_creatives', $token)) {
            throw new \RuntimeException('Invalid CSRF token.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        $content = trim((string) $request->getContent());
        if ('' === $content) {
            return $request->request->all();
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function getCurrentUserId(): int
    {
        return (int) $this->user->getId();
    }

    private function assertCanViewCreatives(): void
    {
        if (null === $this->security || !$this->security->isGranted('ads:intelligence:view')) {
            throw $this->createAccessDeniedException();
        }
    }

    private function getConversationService(): CreativeConversationService
    {
        return $this->container->get('mautic.ads_intelligence.service.creative_conversation');
    }

    private function getGenerationStreamService(): CreativeGenerationStreamService
    {
        return $this->container->get('mautic.ads_intelligence.service.creative_generation_stream');
    }

    private function getMediaService(): CreativeMediaService
    {
        return $this->container->get('mautic.ads_intelligence.service.creative_media');
    }

    private function getGeminiSettings(): GeminiSettingsService
    {
        return $this->container->get('mautic.ads_intelligence.service.gemini_settings');
    }

    private function ensureSchema(): void
    {
        $this->container->get('mautic.ads_intelligence.service.migration_runner')->run();
    }
}
