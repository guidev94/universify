<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution;

use Symfony\Component\HttpFoundation\Request;

class UtmCaptureService
{
    /**
     * UTM parameter names.
     *
     * @var string[]
     */
    private const UTM_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    /**
     * Extracts UTM parameters from the request.
     *
     * @return array{
     *     utmSource: ?string,
     *     utmMedium: ?string,
     *     utmCampaign: ?string,
     *     utmContent: ?string,
     *     utmTerm: ?string
     * }
     */
    public function extractFromRequest(Request $request): array
    {
        return [
            'utmSource'   => $this->getParam($request, 'utm_source'),
            'utmMedium'   => $this->getParam($request, 'utm_medium'),
            'utmCampaign' => $this->getParam($request, 'utm_campaign'),
            'utmContent'  => $this->getParam($request, 'utm_content'),
            'utmTerm'     => $this->getParam($request, 'utm_term'),
        ];
    }

    /**
     * Extracts UTM parameters from a cookie JSON payload.
     *
     * @param array<string, mixed> $cookieData
     *
     * @return array{
     *     utmSource: ?string,
     *     utmMedium: ?string,
     *     utmCampaign: ?string,
     *     utmContent: ?string,
     *     utmTerm: ?string
     * }
     */
    public function extractFromCookieData(array $cookieData): array
    {
        return [
            'utmSource'   => isset($cookieData['utm_source']) ? (string) $cookieData['utm_source'] : null,
            'utmMedium'   => isset($cookieData['utm_medium']) ? (string) $cookieData['utm_medium'] : null,
            'utmCampaign' => isset($cookieData['utm_campaign']) ? (string) $cookieData['utm_campaign'] : null,
            'utmContent'  => isset($cookieData['utm_content']) ? (string) $cookieData['utm_content'] : null,
            'utmTerm'     => isset($cookieData['utm_term']) ? (string) $cookieData['utm_term'] : null,
        ];
    }

    /**
     * Returns true if the request contains at least one UTM parameter.
     */
    public function hasUtmParams(Request $request): bool
    {
        foreach (self::UTM_PARAMS as $param) {
            if ($request->query->get($param) !== null) {
                return true;
            }
        }

        return false;
    }

    private function getParam(Request $request, string $name): ?string
    {
        $value = $request->query->get($name);

        return ($value !== null && $value !== '') ? (string) $value : null;
    }
}
