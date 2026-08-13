<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ActionController extends CommonController
{
    public function indexAction(Request $request, int $page = 1): Response
    {
        return $this->delegateView([
            'viewParameters'  => [
                'page'         => $page,
                'actions'      => [],
                'currentRoute' => 'mautic_ads_intelligence_actions',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Action/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_actions',
                'mauticContent' => 'adsIntelligenceActions',
            ],
        ]);
    }

    public function approveAction(Request $request, int $id): JsonResponse
    {
        // TODO: call ApprovalService::approveAction($id)
        return new JsonResponse(['status' => 'ok', 'id' => $id]);
    }

    public function rejectAction(Request $request, int $id): JsonResponse
    {
        // TODO: call ApprovalService::rejectAction($id)
        return new JsonResponse(['status' => 'ok', 'id' => $id]);
    }

    public function runApprovedAction(Request $request, int $id): JsonResponse
    {
        // TODO: call ActionExecutorService::execute($id)
        return new JsonResponse(['status' => 'ok', 'id' => $id]);
    }
}
