<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\AI;

class CreativeModelCatalog
{
    public const MODE_TEXT  = 'text';
    public const MODE_IMAGE = 'image';
    public const MODE_VIDEO = 'video';

    public const DEFAULT_TEXT_MODEL = 'gemini-3.6-flash';

    /**
     * @return array<string, list<array{id: string, label: string}>>
     */
    public static function getModelsByMode(): array
    {
        return [
            self::MODE_TEXT => [
                ['id' => self::DEFAULT_TEXT_MODEL, 'label' => 'Gemini 3.6 Flash'],
                ['id' => 'gemini-3.5-flash-lite', 'label' => 'Gemini 3.5 Flash-Lite'],
                ['id' => 'gemini-3.1-pro-preview', 'label' => 'Gemini 3.1 Pro (Preview)'],
                ['id' => 'gemini-3.5-flash', 'label' => 'Gemini 3.5 Flash'],
                ['id' => 'gemini-2.5-flash', 'label' => 'Gemini 2.5 Flash'],
                ['id' => 'gemini-2.5-pro', 'label' => 'Gemini 2.5 Pro'],
            ],
            self::MODE_IMAGE => [
                ['id' => 'gemini-3.1-flash-image-preview', 'label' => 'Gemini 3.1 Flash Image (Preview)'],
                ['id' => 'gemini-2.5-flash-image', 'label' => 'Gemini 2.5 Flash Image'],
            ],
            self::MODE_VIDEO => [
                ['id' => 'veo-3.1-generate-preview', 'label' => 'Veo 3.1'],
                ['id' => 'veo-3.1-fast-generate-preview', 'label' => 'Veo 3.1 Fast'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function getModes(): array
    {
        return [self::MODE_TEXT, self::MODE_IMAGE, self::MODE_VIDEO];
    }

    public static function isValidMode(string $mode): bool
    {
        return in_array($mode, self::getModes(), true);
    }

    public static function isValidModel(string $mode, string $model): bool
    {
        $models = self::getModelsByMode()[$mode] ?? [];
        foreach ($models as $item) {
            if ($item['id'] === $model) {
                return true;
            }
        }

        return false;
    }

    public static function defaultModel(string $mode): string
    {
        $models = self::getModelsByMode()[$mode] ?? [];

        return $models[0]['id'] ?? self::DEFAULT_TEXT_MODEL;
    }

    public static function supportsVideoExtend(string $model): bool
    {
        return str_contains($model, 'veo-3.1') && !str_contains($model, 'fast');
    }

    public static function isImagenModel(string $model): bool
    {
        return str_starts_with($model, 'imagen-');
    }
}
