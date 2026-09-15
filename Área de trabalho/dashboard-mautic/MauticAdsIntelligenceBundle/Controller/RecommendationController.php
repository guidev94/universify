<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsRecommendation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RecommendationController extends CommonController
{
    public function indexAction(Request $request, int $page = 1): Response
    {
        $recommendations = $this->doctrine->getManager()->getRepository(AdsRecommendation::class)->findBy([], [
            'status'        => 'ASC',
            'priorityScore' => 'DESC',
            'createdAt'     => 'DESC',
        ], 100);

        return $this->delegateView([
            'viewParameters'  => [
                'page'            => $page,
                'recommendations' => $recommendations,
                'currentRoute'    => 'mautic_ads_intelligence_recommendations',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Recommendation/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_recommendations',
                'mauticContent' => 'adsIntelligenceRecommendations',
            ],
        ]);
    }

    public function viewAction(Request $request, int $id): Response
    {
        return $this->delegateView([
            'viewParameters'  => [
                'id'             => $id,
                'recommendation' => null,
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Recommendation/index.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'adsIntelligenceRecommendations',
            ],
        ]);
    }

    public function approveAction(Request $request, int $id): JsonResponse
    {
        // TODO: call ApprovalService::approveRecommendation($id)
        return new JsonResponse(['status' => 'ok', 'id' => $id]);
    }

    public function rejectAction(Request $request, int $id): JsonResponse
    {
        // TODO: call ApprovalService::rejectRecommendation($id)
        return new JsonResponse(['status' => 'ok', 'id' => $id]);
    }
}
