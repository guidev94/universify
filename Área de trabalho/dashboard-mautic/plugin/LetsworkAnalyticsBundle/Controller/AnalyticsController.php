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
    private const VALID_TABS = ['overview', 'conversao', 'segmentos', 'pipeline', 'perdas', 'utm'];

    private AnalyticsModel $analyticsModel;

    #[Required]
    public function setAnalyticsModel(AnalyticsModel $analyticsModel): void
    {
        $this->analyticsModel = $analyticsModel;
    }

    public function aiTabAction(Request $request, string $tab): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        // Playbook: ai/tab/playbook_linkedin_123
        if (preg_match('/^playbook_([a-z_]+)_(\d+)$/', $tab, $m)) {
            $result = $this->analyticsModel->getPlaybookMessage((int)$m[2], $m[1]);
            return new JsonResponse(['analysis' => $result]);
        }

        // Summary: ai/tab/summary_123
        if (preg_match('/^summary_(\d+)$/', $tab, $m)) {
            $result = $this->analyticsModel->getLeadSummary((int)$m[1]);
            return new JsonResponse(['analysis' => $result]);
        }

        // Classify: ai/tab/classify_123
        if (preg_match('/^classify_(\d+)$/', $tab, $m)) {
            $message = $request->get('message', '');
            $result = $this->analyticsModel->classifyLinkedinResponse((int)$m[1], $message);
            return new JsonResponse(['analysis' => $result]);
        }

        $dateFrom = $request->get('dateFrom', date('Y-m-d', strtotime('-90 days')));
        $dateTo = $request->get('dateTo', date('Y-m-d'));
        $result = $this->analyticsModel->getAiTabAnalysis($tab, ['from' => $dateFrom, 'to' => $dateTo]);
        return new JsonResponse(['analysis' => $result]);
    }

    public function aiInsightsAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $dateFrom = $request->get('dateFrom', date('Y-m-d', strtotime('-90 days')));
        $dateTo = $request->get('dateTo', date('Y-m-d'));
        $insights = $this->analyticsModel->getAiInsights(['from' => $dateFrom, 'to' => $dateTo]);
        return new JsonResponse(['insights' => $insights]);
    }

    public function leadDetailAction(Request $request, int $leadId): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $include = $request->get('include', '');

        if ($include === 'timeline') {
            return new JsonResponse(['timeline' => $this->analyticsModel->getLeadTimeline($leadId)]);
        }

        $lead = $this->analyticsModel->getLeadDetail($leadId);
        if (!$lead) {
            return new JsonResponse(['error' => 'Lead não encontrado'], 404);
        }

        $response = ['lead' => $lead];
        if ($include === 'tags') {
            $response['tags'] = $this->analyticsModel->getLeadTags($leadId);
        }

        return new JsonResponse($response);
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

    public function updateLeadAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $leadId = (int) $request->request->get('lead_id');
        $field = $request->request->get('field', '');
        $value = $request->request->get('value', '');

        if (!$leadId || !$field) {
            return new JsonResponse(['error' => 'lead_id e field obrigatórios'], 400);
        }

        // Enrich lead (completar email/telefone)
        if ($field === 'enrich') {
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            return new JsonResponse($this->analyticsModel->enrichLead($leadId, $email, $phone));
        }

        // Webhook event (Roboze/LetsWork CRM)
        if ($field === 'webhook_event') {
            $source = $request->request->get('source', '');
            $event = $request->request->get('event', '');
            $data = $request->request->all();
            return new JsonResponse($this->analyticsModel->processWebhookEvent($source, $event, $leadId, $data));
        }

        // Score event
        if ($field === 'score_event') {
            $event = $request->request->get('event', '');
            $description = $request->request->get('description', '');
            return new JsonResponse($this->analyticsModel->addScoreEvent($leadId, $event, $description));
        }

        $result = $this->analyticsModel->updateLeadField($leadId, $field, $value);
        return new JsonResponse($result);
    }

    public function incompleteQueueAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }
        return new JsonResponse(['leads' => $this->analyticsModel->getIncompleteQueue()]);
    }

    public function enrichLeadAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $leadId = (int) $request->request->get('lead_id');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');

        if (!$leadId) return new JsonResponse(['error' => 'lead_id obrigatório'], 400);

        $result = $this->analyticsModel->enrichLead($leadId, $email, $phone);
        return new JsonResponse($result);
    }

    public function playbookAction(Request $request, int $leadId, string $tipo): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }
        return new JsonResponse($this->analyticsModel->getPlaybookMessage($leadId, $tipo));
    }

    public function webhookEventAction(Request $request): JsonResponse
    {
        $source = $request->request->get('source', '');
        $event = $request->request->get('event', '');
        $leadId = (int) $request->request->get('lead_id');
        $data = $request->request->all();

        if (!$source || !$event || !$leadId) {
            return new JsonResponse(['error' => 'source, event e lead_id obrigatórios'], 400);
        }

        $result = $this->analyticsModel->processWebhookEvent($source, $event, $leadId, $data);
        return new JsonResponse($result);
    }

    public function pushToCrmAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }
        $leadId = (int) $request->request->get('lead_id');
        $responsavel = $request->request->get('responsavel', '');
        if (!$leadId || !$responsavel) return new JsonResponse(['error' => 'lead_id e responsavel obrigatórios'], 400);
        return new JsonResponse($this->analyticsModel->pushLeadToCrm($leadId, $responsavel));
    }

    public function forecastAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }
        $dateFrom = $request->get('dateFrom', date('Y-m-d', strtotime('-90 days')));
        $dateTo = $request->get('dateTo', date('Y-m-d'));
        return new JsonResponse($this->analyticsModel->getForecast(['from' => $dateFrom, 'to' => $dateTo]));
    }

    public function duplicatesAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }
        return new JsonResponse(['duplicates' => $this->analyticsModel->getDuplicateLeads()]);
    }

    public function scoreEventAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $leadId = (int) $request->request->get('lead_id');
        $event = $request->request->get('event', '');
        $description = $request->request->get('description', '');

        if (!$leadId || !$event) {
            return new JsonResponse(['error' => 'lead_id e event são obrigatórios'], 400);
        }

        $result = $this->analyticsModel->addScoreEvent($leadId, $event, $description);
        return new JsonResponse($result);
    }

    public function leadTimelineAction(Request $request, int $leadId): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $timeline = $this->analyticsModel->getLeadTimeline($leadId);
        return new JsonResponse(['timeline' => $timeline]);
    }

    public function classifyLinkedinAction(Request $request): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $leadId = (int) $request->request->get('lead_id');
        $message = $request->request->get('message', '');

        if (!$leadId || !$message) {
            return new JsonResponse(['error' => 'lead_id e message são obrigatórios'], 400);
        }

        $result = $this->analyticsModel->classifyLinkedinResponse($leadId, $message);
        return new JsonResponse(['classification' => $result]);
    }

    public function leadSummaryAction(Request $request, int $leadId): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $summary = $this->analyticsModel->getLeadSummary($leadId);
        return new JsonResponse(['summary' => $summary]);
    }

    public function stageDetailAction(Request $request, int $stageId): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $dateFrom = $request->get('dateFrom', date('Y-m-d', strtotime('-90 days')));
        $dateTo = $request->get('dateTo', date('Y-m-d'));
        $leads = $this->analyticsModel->getLeadsByStage($stageId, ['from' => $dateFrom, 'to' => $dateTo]);
        return new JsonResponse(['leads' => $leads]);
    }

    public function segmentoDetailAction(Request $request, string $segmento): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $dateFrom = $request->get('dateFrom', date('Y-m-d', strtotime('-90 days')));
        $dateTo = $request->get('dateTo', date('Y-m-d'));
        $leads = $this->analyticsModel->getLeadsBySegment($segmento, ['from' => $dateFrom, 'to' => $dateTo]);
        return new JsonResponse(['leads' => $leads]);
    }

    public function kpiDetailAction(Request $request, string $kpi): JsonResponse
    {
        if (!$this->security->isGranted('letsworkAnalytics:analytics:view')) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        $dateFrom = $request->get('dateFrom', date('Y-m-d', strtotime('-90 days')));
        $dateTo = $request->get('dateTo', date('Y-m-d'));
        $modulo = $request->get('modulo', '');
        $result = $this->analyticsModel->getLeadsByKpi($kpi, ['from' => $dateFrom, 'to' => $dateTo, 'modulo' => $modulo]);

        if (is_array($result) && isset($result['type'])) {
            return new JsonResponse($result);
        }

        return new JsonResponse(['leads' => $result]);
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
            'utm' => $this->analyticsModel->getUtmData($dateRange),
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
