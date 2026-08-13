<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsLeadAttribution;
use MauticPlugin\MauticAdsIntelligenceBundle\Repository\AdsLeadAttributionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadAttributionService
{
    private const COOKIE_NAME = 'mautic_ads_ai_attribution';
    private const COOKIE_TTL  = 90 * 24 * 3600; // 90 days

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClickIdCaptureService $clickIdCaptureService,
        private readonly UtmCaptureService $utmCaptureService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Captures attribution data from the request and persists/updates the lead attribution record.
     * Should be called when a lead is created or when attribution parameters are present.
     */
    public function captureFromRequest(int $leadId, Request $request): ?AdsLeadAttribution
    {
        $clickData = $this->clickIdCaptureService->extractFromRequest($request);
        $utmData   = $this->utmCaptureService->extractFromRequest($request);

        // Also try reading from cookie if request has no direct params
        if ($clickData['clickId'] === null && !$this->utmCaptureService->hasUtmParams($request)) {
            $cookieAttribution = $this->readCookieAttribution($request);

            if ($cookieAttribution !== null) {
                $cookieData = $cookieAttribution['last_touch'] ?? $cookieAttribution['first_touch'] ?? [];
                if (is_array($cookieData)) {
                    $clickData = $this->clickIdCaptureService->extractFromCookieData($cookieData);
                    $utmData   = $this->utmCaptureService->extractFromCookieData($cookieData);
                }
            }
        }

        // If no attribution data at all, skip
        if ($clickData['clickId'] === null && $utmData['utmSource'] === null) {
            return null;
        }

        /** @var AdsLeadAttributionRepository $repo */
        $repo        = $this->em->getRepository(AdsLeadAttribution::class);
        $attribution = $repo->findByLeadId($leadId);
        $now         = new \DateTime();

        if ($attribution === null) {
            $attribution = new AdsLeadAttribution();
            $attribution->setLeadId($leadId);
            $attribution->setFirstTouchAt($now);
        }

        // Always update last touch
        $attribution->setLastTouchAt($now);

        // Set click ID data
        if ($clickData['clickId'] !== null) {
            $attribution->setClickId($clickData['clickId']);
            $attribution->setClickIdType($clickData['clickIdType']);
            $attribution->setProvider($clickData['provider']);
        }

        // Set UTM data
        if ($utmData['utmSource'] !== null) {
            $attribution->setUtmSource($utmData['utmSource']);
            $attribution->setSource($utmData['utmSource']);
        }

        if ($utmData['utmMedium'] !== null) {
            $attribution->setUtmMedium($utmData['utmMedium']);
            $attribution->setMedium($utmData['utmMedium']);
        }

        if ($utmData['utmCampaign'] !== null) {
            $attribution->setUtmCampaign($utmData['utmCampaign']);
        }

        if ($utmData['utmContent'] !== null) {
            $attribution->setUtmContent($utmData['utmContent']);
        }

        if ($utmData['utmTerm'] !== null) {
            $attribution->setUtmTerm($utmData['utmTerm']);
        }

        $attribution->setUpdatedAt($now);

        try {
            $this->em->persist($attribution);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->logger->error('AdsIntelligence: failed to persist attribution for lead '.$leadId.': '.$e->getMessage());

            return null;
        }

        return $attribution;
    }

    /**
     * Sets the attribution cookie on the response.
     * Stores both first_touch and last_touch data.
     */
    public function setAttributionCookie(Response $response, Request $request): void
    {
        $clickData = $this->clickIdCaptureService->extractFromRequest($request);
        $utmData   = $this->utmCaptureService->extractFromRequest($request);

        if ($clickData['clickId'] === null && !$this->utmCaptureService->hasUtmParams($request)) {
            return;
        }

        $touchData = [
            'provider'       => $clickData['provider'],
            'utm_source'     => $utmData['utmSource'],
            'utm_medium'     => $utmData['utmMedium'],
            'utm_campaign'   => $utmData['utmCampaign'],
            'utm_content'    => $utmData['utmContent'],
            'utm_term'       => $utmData['utmTerm'],
            'click_id'       => $clickData['clickId'],
            'click_id_type'  => $clickData['clickIdType'],
            'timestamp'      => (new \DateTime())->format(\DateTimeInterface::ATOM),
        ];

        $existing = $this->readCookieAttribution($request);

        $cookiePayload = [
            'first_touch' => $existing['first_touch'] ?? $touchData,
            'last_touch'  => $touchData,
        ];

        $cookie = Cookie::create(
            self::COOKIE_NAME,
            json_encode($cookiePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            time() + self::COOKIE_TTL,
            '/',
            null,
            false,
            false,
            false,
            Cookie::SAMESITE_LAX,
        );

        $response->headers->setCookie($cookie);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readCookieAttribution(Request $request): ?array
    {
        $raw = $request->cookies->get(self::COOKIE_NAME);

        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            $data = json_decode((string) $raw, true, 10, JSON_THROW_ON_ERROR);

            return is_array($data) ? $data : null;
        } catch (\JsonException) {
            return null;
        }
    }
}
