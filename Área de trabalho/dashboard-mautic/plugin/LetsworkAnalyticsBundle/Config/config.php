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
