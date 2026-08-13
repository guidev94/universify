<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution;

use Symfony\Component\HttpFoundation\Request;

class ClickIdCaptureService
{
    /**
     * Click ID parameter names mapped to their provider type.
     *
     * @var array<string, string>
     */
    private const CLICK_ID_PARAMS = [
        'gclid'    => 'google',
        'gbraid'   => 'google',
        'wbraid'   => 'google',
        'msclkid'  => 'microsoft',
        'fbclid'   => 'meta',
        'li_fat_id' => 'linkedin',
        'ttclid'   => 'tiktok',
    ];

    /**
     * Extracts click ID parameters from the request.
     *
     * @return array{clickId: ?string, clickIdType: ?string, provider: ?string}
     */
    public function extractFromRequest(Request $request): array
    {
        foreach (self::CLICK_ID_PARAMS as $param => $provider) {
            $value = $request->query->get($param);

            if ($value !== null && $value !== '') {
                return [
                    'clickId'     => (string) $value,
                    'clickIdType' => $param,
                    'provider'    => $provider,
                ];
            }
        }

        return [
            'clickId'     => null,
            'clickIdType' => null,
            'provider'    => null,
        ];
    }

    /**
     * Extracts click ID parameters from a cookie JSON payload.
     *
     * @param array<string, mixed> $cookieData
     *
     * @return array{clickId: ?string, clickIdType: ?string, provider: ?string}
     */
    public function extractFromCookieData(array $cookieData): array
    {
        $clickId     = isset($cookieData['click_id']) ? (string) $cookieData['click_id'] : null;
        $clickIdType = isset($cookieData['click_id_type']) ? (string) $cookieData['click_id_type'] : null;
        $provider    = $clickIdType !== null ? (self::CLICK_ID_PARAMS[$clickIdType] ?? null) : null;

        return [
            'clickId'     => $clickId,
            'clickIdType' => $clickIdType,
            'provider'    => $provider,
        ];
    }

    /**
     * Returns the provider name for a given click ID type.
     */
    public function resolveProvider(string $clickIdType): ?string
    {
        return self::CLICK_ID_PARAMS[$clickIdType] ?? null;
    }
}
