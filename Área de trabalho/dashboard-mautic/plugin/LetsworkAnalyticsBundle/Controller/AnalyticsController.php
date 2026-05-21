<?php

namespace MauticPlugin\LetsworkAnalyticsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\LetsworkAnalyticsBundle\Model\AnalyticsModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

class AnalyticsController extends CommonController
{
    private const VALID_TABS = ['overview', 'conversao', 'segmentos', 'pipeline', 'perdas'];

    private AnalyticsModel $analyticsModel;

    #[Required]
    public function setAnalyticsModel(AnalyticsModel $analyticsModel): void
    {
        $this->analyticsModel = $analyticsModel;
    }

    public function leadDetailAction(Request $request, int $leadId): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $lead = $this->analyticsModel->getLeadDetail($leadId);
        if (!$lead) {
            return new JsonResponse(['error' => 'Lead não encontrado'], 404);
        }

        return new JsonResponse(['lead' => $lead]);
    }

    public function aiSuggestAction(Request $request, int $leadId): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $lead = $this->analyticsModel->getLeadDetail($leadId);
        if (!$lead) {
            return new JsonResponse(['error' => 'Lead não encontrado'], 404);
        }

        $suggestion = $this->analyticsModel->getAiSuggestion($lead);
        return new JsonResponse(['suggestion' => $suggestion]);
    }

    public function scheduleFollowupAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $leadId = (int) $request->request->get('lead_id');
        $date = $request->request->get('date');
        $note = $request->request->get('note', '');

        if (!$leadId || !$date) {
            return new JsonResponse(['error' => 'lead_id e date são obrigatórios'], 400);
        }

        $result = $this->analyticsModel->scheduleFollowup($leadId, $date, $note);
        return new JsonResponse($result);
    }

    public function sendEmailAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $leadId = (int) $request->request->get('lead_id');
        $subject = $request->request->get('subject', '');
        $body = $request->request->get('body', '');

        if (!$leadId || !$subject || !$body) {
            return new JsonResponse(['error' => 'lead_id, subject e body são obrigatórios'], 400);
        }

        $result = $this->analyticsModel->sendEmailToLead($leadId, $subject, $body);
        return new JsonResponse($result);
    }

    public function kpiDetailAction(Request $request, string $kpi): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $dateFrom = $request->get('dateFrom', date('Y-m-d', strtotime('-90 days')));
        $dateTo = $request->get('dateTo', date('Y-m-d'));
        $leads = $this->analyticsModel->getLeadsByKpi($kpi, ['from' => $dateFrom, 'to' => $dateTo]);

        return new JsonResponse(['leads' => $leads]);
    }

    public function indexAction(Request $request, string $tab = 'overview'): Response
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return $this->accessDenied();
        }

        if (!in_array($tab, self::VALID_TABS)) {
            $tab = 'overview';
        }

        $dateFrom = $request->get('dateFrom', date('Y-m-d', strtotime('-90 days')));
        $dateTo = $request->get('dateTo', date('Y-m-d'));
        $dateRange = ['from' => $dateFrom, 'to' => $dateTo];

        $data = match ($tab) {
            'overview' => $this->analyticsModel->getOverviewData($dateRange),
            'conversao' => $this->analyticsModel->getConversaoData($dateRange),
            'segmentos' => $this->analyticsModel->getSegmentosData($dateRange),
            'pipeline' => $this->analyticsModel->getPipelineData($dateRange),
            'perdas' => $this->analyticsModel->getPerdasData($dateRange),
            default => $this->analyticsModel->getOverviewData($dateRange),
        };

        return $this->delegateView([
            'viewParameters' => array_merge($data, [
                'activeTab'  => $tab,
                'dateFrom'   => $dateFrom,
                'dateTo'     => $dateTo,
                'tabs'       => self::VALID_TABS,
            ]),
            'contentTemplate' => '@LetsworkAnalytics/Analytics/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#letswork_analytics_index',
                'mauticContent' => 'letsworkAnalytics',
                'route'         => $this->generateUrl('letswork_analytics_index', ['tab' => $tab]),
            ],
        ]);
    }
}
