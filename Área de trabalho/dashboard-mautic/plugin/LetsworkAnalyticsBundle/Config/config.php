<?php

return [
    'name'        => 'Letswork CRM Analytics',
    'description' => 'Dashboard analytics premium para leads, conversao e pipeline.',
    'version'     => '1.0.0',
    'author'      => 'Letswork',

    'routes' => [
        'main' => [
            'letswork_analytics_index' => [
                'path'       => '/analytics/{tab}',
                'controller' => 'MauticPlugin\LetsworkAnalyticsBundle\Controller\AnalyticsController::indexAction',
                'defaults'   => ['tab' => 'overview'],
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
