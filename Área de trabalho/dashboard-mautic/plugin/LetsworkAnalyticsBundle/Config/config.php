<?php

return [
    'name'        => 'Letswork CRM Analytics',
    'description' => 'Dashboard analytics premium para leads, conversao e pipeline.',
    'version'     => '2.0.0',
    'author'      => 'Letswork',

    'routes' => [
        'main' => [
            'letswork_analytics_incomplete_queue' => [
                'path'       => '/analytics/leads/incomplete',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::incompleteQueueAction',
            ],
            'letswork_analytics_enrich_lead' => [
                'path'       => '/analytics/action/enrich-lead',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::enrichLeadAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_playbook' => [
                'path'       => '/analytics/ai/playbook/{leadId}/{tipo}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::playbookAction',
            ],
            'letswork_analytics_webhook' => [
                'path'       => '/analytics/webhook/event',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::webhookEventAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_score_event' => [
                'path'       => '/analytics/action/score-event',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::scoreEventAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_lead_timeline' => [
                'path'       => '/analytics/lead/{leadId}/timeline',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::leadTimelineAction',
            ],
            'letswork_analytics_lead_campaigns' => [
                'path'       => '/analytics/lead/{leadId}/campaigns',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::leadCampaignsAction',
            ],
            'letswork_analytics_classify_linkedin' => [
                'path'       => '/analytics/ai/classify-linkedin',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::classifyLinkedinAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_lead_summary' => [
                'path'       => '/analytics/ai/summary/{leadId}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::leadSummaryAction',
            ],
            'letswork_analytics_stage_detail' => [
                'path'       => '/analytics/stage-detail/{stageId}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::stageDetailAction',
            ],
            'letswork_analytics_segmento_detail' => [
                'path'       => '/analytics/segmento-detail/{segmento}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::segmentoDetailAction',
            ],
            'letswork_analytics_kpi_detail' => [
                'path'       => '/analytics/kpi-detail/{kpi}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::kpiDetailAction',
            ],
            'letswork_analytics_lead_detail' => [
                'path'       => '/analytics/lead/{leadId}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::leadDetailAction',
            ],
            'letswork_analytics_ai_suggest' => [
                'path'       => '/analytics/ai/suggest/{leadId}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::aiSuggestAction',
            ],
            'letswork_analytics_ai_generate_email' => [
                'path'       => '/analytics/ai/generate-email/{leadId}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::aiGenerateEmailAction',
            ],
            'letswork_analytics_notifications' => [
                'path'       => '/analytics/notifications',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::notificationsAction',
            ],
            'letswork_analytics_smart_alerts' => [
                'path'       => '/analytics/smart-alerts',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::smartAlertsAction',
            ],
            'letswork_analytics_campaign_webhook' => [
                'path'       => '/analytics/webhook/campaign',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::campaignWebhookAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_trigger_campaign' => [
                'path'       => '/analytics/action/trigger-campaign',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::triggerCampaignAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_send_whatsapp' => [
                'path'       => '/analytics/action/send-whatsapp',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::sendWhatsAppAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_calendar_event' => [
                'path'       => '/analytics/action/calendar-event',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::createCalendarEventAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_enrich_cnpj' => [
                'path'       => '/analytics/action/enrich-cnpj',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::enrichCnpjAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_push_crm' => [
                'path'       => '/analytics/action/push-crm',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::pushToCrmAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_forecast' => [
                'path'       => '/analytics/forecast',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::forecastAction',
            ],
            'letswork_analytics_duplicates' => [
                'path'       => '/analytics/duplicates',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::duplicatesAction',
            ],
            'letswork_analytics_predictive_score' => [
                'path'       => '/analytics/ai/predictive-score/{leadId}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::predictiveScoreAction',
            ],
            'letswork_analytics_auto_classify' => [
                'path'       => '/analytics/ai/auto-classify/{leadId}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::autoClassifyAction',
            ],
            'letswork_analytics_ia_coach' => [
                'path'       => '/analytics/ai/coach/{leadId}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::iaCoachAction',
            ],
            'letswork_analytics_cadencias' => [
                'path'       => '/analytics/cadencias',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::cadenciasAction',
            ],
            'letswork_analytics_create_cadencia' => [
                'path'       => '/analytics/action/create-cadencia',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::createCadenciaAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_add_lead_cadencia' => [
                'path'       => '/analytics/action/add-lead-cadencia',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::addLeadCadenciaAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_weekly_report' => [
                'path'       => '/analytics/report/weekly',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::weeklyReportAction',
            ],
            'letswork_analytics_comparativo' => [
                'path'       => '/analytics/comparativo',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::comparativoAction',
            ],
            'letswork_analytics_ai_insights' => [
                'path'       => '/analytics/ai/insights',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::aiInsightsAction',
            ],
            'letswork_analytics_ai_tab' => [
                'path'       => '/analytics/ai/tab/{tab}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::aiTabAction',
            ],
            'letswork_analytics_followup' => [
                'path'       => '/analytics/action/followup',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::scheduleFollowupAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_send_email' => [
                'path'       => '/analytics/action/send-email',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::sendEmailAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_update_lead' => [
                'path'       => '/analytics/action/update-lead',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::updateLeadAction',
                'method'     => 'POST',
            ],
            'letswork_analytics_index' => [
                'path'       => '/analytics/{tab}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::indexAction',
                'defaults'   => ['tab' => 'overview'],
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'letswork.analytics' => [
                'route'     => 'letswork_analytics_index',
                'access'    => 'letsworkAnalytics:analytics:view',
                'iconClass' => 'ri-bar-chart-box-fill',
                'priority'  => 95,
            ],
        ],
    ],

    'services' => [
        'others' => [
            'letswork.analytics.model' => [
                'class'     => 'MauticPlugin\LetsworkAnalyticsBundle\Model\AnalyticsModel',
                'arguments' => ['doctrine.orm.entity_manager'],
            ],
        ],
    ],
];
