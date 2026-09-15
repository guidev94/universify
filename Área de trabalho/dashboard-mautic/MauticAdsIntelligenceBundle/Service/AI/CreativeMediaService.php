<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCreativeMessage;
use Symfony\Component\HttpFoundation\File\File;

class CreativeMediaService
{
    public function __construct(
        private readonly PathsHelper $pathsHelper,
        private readonly CoreParametersHelper $coreParametersHelper,
        private readonly AssetModel $assetModel,
    ) {
    }

    public function getBaseDir(): string
    {
        return rtrim($this->pathsHelper->getMediaPath(), '/\\').'/ads-intelligence/creatives';
    }

    public function ensureConversationDir(int $conversationId): string
    {
        $dir = $this->getBaseDir().'/'.$conversationId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Unable to create media directory: %s', $dir));
        }

        return $dir;
    }

    /**
     * @return array{absolutePath: string, relativePath: string, mimeType: string, fileSize: int}
     */
    public function storeBinary(int $conversationId, string $bytes, string $extension, string $mimeType): array
    {
        $dir      = $this->ensureConversationDir($conversationId);
        $filename = sprintf('%s_%s.%s', date('YmdHis'), bin2hex(random_bytes(4)), ltrim($extension, '.'));
        $absolute = $dir.'/'.$filename;

        if (false === file_put_contents($absolute, $bytes)) {
            throw new \RuntimeException('Unable to write creative media file.');
        }

        return [
            'absolutePath' => $absolute,
            'relativePath' => sprintf('ads-intelligence/creatives/%d/%s', $conversationId, $filename),
            'mimeType'     => $mimeType,
            'fileSize'     => strlen($bytes),
        ];
    }

    public function resolveAbsolutePath(AdsCreativeMessage $message): ?string
    {
        $relative = $message->getMediaPath();
        if (null === $relative || '' === $relative) {
            return null;
        }

        if (str_starts_with($relative, '/') || preg_match('#^[A-Za-z]:\\\\#', $relative)) {
            return is_file($relative) ? $relative : null;
        }

        $absolute = rtrim($this->pathsHelper->getMediaPath(), '/\\').'/'.ltrim($relative, '/');

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * @return array{assetId: int, assetUrl: string}
     */
    public function saveMessageToAsset(AdsCreativeMessage $message, string $title): array
    {
        $absolute = $this->resolveAbsolutePath($message);
        if (null === $absolute) {
            throw new \RuntimeException('Message has no media file to save as asset.');
        }

        $uploadDir = (string) $this->coreParametersHelper->get('upload_dir');
        if ('' === $uploadDir) {
            throw new \RuntimeException('Mautic upload_dir is not configured.');
        }

        // Asset::upload() moves the source file; copy first so studio media remains available.
        $tempDir = sys_get_temp_dir().'/mautic-ads-creatives';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            throw new \RuntimeException('Unable to create temporary directory for asset upload.');
        }

        $tempPath = $tempDir.'/'.basename($absolute);
        if (!copy($absolute, $tempPath)) {
            throw new \RuntimeException('Unable to copy media file for asset upload.');
        }

        $asset = $this->assetModel->getEntity();
        $asset->setTitle($title);
        $asset->setUploadDir($uploadDir);
        $asset->setFile(new File($tempPath));
        $asset->preUpload();
        $asset->upload();
        $this->assetModel->saveEntity($asset);

        if (is_file($tempPath)) {
            @unlink($tempPath);
        }

        $url = $this->assetModel->generateUrl($asset, true);

        return [
            'assetId'  => (int) $asset->getId(),
            'assetUrl' => $url,
        ];
    }

    public function extensionFromMime(string $mimeType): string
    {
        return match (strtolower($mimeType)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp'              => 'webp',
            'image/gif'               => 'gif',
            'video/mp4'               => 'mp4',
            'video/webm'              => 'webm',
            default                   => 'png',
        };
    }

    /**
     * @return array{mimeType: string, data: string}
     */
    public function toInlineData(string $absolutePath): array
    {
        $bytes = file_get_contents($absolutePath);
        if (false === $bytes || '' === $bytes) {
            throw new \RuntimeException('Unable to read media file for Gemini upload.');
        }

        $mime = mime_content_type($absolutePath) ?: 'application/octet-stream';

        return [
            'mimeType' => $mime,
            'data'     => base64_encode($bytes),
        ];
    }
}
