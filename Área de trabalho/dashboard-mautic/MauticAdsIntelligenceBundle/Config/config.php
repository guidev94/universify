<?php

declare(strict_types=1);

return [
    'name'        => 'Mautic Ads Intelligence',
    'description' => 'Integra dados de anúncios com CRM para otimização por IA',
    'version'     => '0.1.14',
    'author'      => 'LetsWork',

    'routes' => [
        'public' => [
            'mautic_ads_intelligence_offline_conversions_google_feed' => [
                'path'       => '/ads-intelligence/offline-conversions/google-feed.csv',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::googleFeedAction',
                'method'     => 'GET',
            ],
            'mautic_ads_intelligence_offline_conversions_bing_feed' => [
                'path'       => '/ads-intelligence/offline-conversions/bing-feed.csv',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::bingFeedAction',
                'method'     => 'GET',
            ],
        ],
        'main' => [
            // Dashboard
            'mautic_ads_intelligence_overview' => [
                'path'       => '/ads-intelligence/overview',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\DashboardController::overviewAction',
            ],
            'mautic_ads_intelligence_channels' => [
                'path'       => '/ads-intelligence/channels',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\DashboardController::channelsAction',
            ],
            'mautic_ads_intelligence_click_performance' => [
                'path'       => '/ads-intelligence/click-performance',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\DashboardController::clickPerformanceAction',
            ],
            'mautic_ads_intelligence_keyword_performance' => [
                'path'       => '/ads-intelligence/keyword-performance',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\DashboardController::keywordPerformanceAction',
            ],
            'mautic_ads_intelligence_campaigns' => [
                'path'       => '/ads-intelligence/campaigns',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CampaignController::indexAction',
            ],
            'mautic_ads_intelligence_campaigns_sync' => [
                'path'       => '/ads-intelligence/campaigns/sync',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CampaignController::syncAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_campaigns_create' => [
                'path'       => '/ads-intelligence/campaigns/create',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CampaignController::createAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_keyword_create' => [
                'path'       => '/ads-intelligence/campaign/{campaignId}/keyword/create',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CampaignController::createKeywordAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_keyword_delete' => [
                'path'       => '/ads-intelligence/keyword/{keywordId}/delete',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CampaignController::deleteKeywordAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_attributed_leads' => [
                'path'       => '/ads-intelligence/attributed-leads',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\DashboardController::attributedLeadsAction',
            ],
            'mautic_ads_intelligence_offline_conversions' => [
                'path'       => '/ads-intelligence/offline-conversions',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::indexAction',
            ],
            'mautic_ads_intelligence_offline_conversions_save' => [
                'path'       => '/ads-intelligence/offline-conversions/save',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::saveAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_offline_conversions_enqueue' => [
                'path'       => '/ads-intelligence/offline-conversions/enqueue',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::enqueueAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_offline_conversions_sync' => [
                'path'       => '/ads-intelligence/offline-conversions/sync',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::syncAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_offline_conversions_download' => [
                'path'         => '/ads-intelligence/offline-conversions/download/{provider}',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::downloadAction',
                'method'       => 'GET',
                'requirements' => ['provider' => 'google|microsoft'],
            ],
            'mautic_ads_intelligence_offline_conversions_create_action' => [
                'path'       => '/ads-intelligence/offline-conversions/create-google-action',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::createGoogleActionAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_offline_conversions_create_ms_action' => [
                'path'       => '/ads-intelligence/offline-conversions/create-ms-action',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\OfflineConversionController::createMicrosoftActionAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_logs' => [
                'path'       => '/ads-intelligence/logs',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\DashboardController::logsAction',
            ],

            // Accounts
            'mautic_ads_intelligence_accounts' => [
                'path'       => '/ads-intelligence/accounts/{page}',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\AccountController::indexAction',
                'defaults'   => ['page' => 1],
                'requirements' => ['page' => '\\d+'],
            ],
            'mautic_ads_intelligence_accounts_sync' => [
                'path'       => '/ads-intelligence/accounts/sync',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\AccountController::syncAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_account_action' => [
                'path'       => '/ads-intelligence/account/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\AccountController::executeAction',
                'defaults'   => ['objectId' => 0],
            ],

            // Campaigns (sync view)
            'mautic_ads_intelligence_campaigns_list' => [
                'path'       => '/ads-intelligence/campaigns-list/{page}',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CampaignController::indexAction',
                'defaults'   => ['page' => 1],
            ],

            // Recommendations
            'mautic_ads_intelligence_recommendations' => [
                'path'       => '/ads-intelligence/recommendations/{page}',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\RecommendationController::indexAction',
                'defaults'   => ['page' => 1],
            ],
            'mautic_ads_intelligence_recommendation_view' => [
                'path'       => '/ads-intelligence/recommendation/{id}/view',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\RecommendationController::viewAction',
            ],
            'mautic_ads_intelligence_recommendation_approve' => [
                'path'       => '/ads-intelligence/recommendation/{id}/approve',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\RecommendationController::approveAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_recommendation_reject' => [
                'path'       => '/ads-intelligence/recommendation/{id}/reject',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\RecommendationController::rejectAction',
                'method'     => 'POST',
            ],

            // Actions
            'mautic_ads_intelligence_actions' => [
                'path'       => '/ads-intelligence/actions/{page}',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\ActionController::indexAction',
                'defaults'   => ['page' => 1],
            ],
            'mautic_ads_intelligence_action_approve' => [
                'path'       => '/ads-intelligence/action/{id}/approve',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\ActionController::approveAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_action_reject' => [
                'path'       => '/ads-intelligence/action/{id}/reject',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\ActionController::rejectAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_action_execute' => [
                'path'       => '/ads-intelligence/action/{id}/execute',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\ActionController::runApprovedAction',
                'method'     => 'POST',
            ],

            // Settings
            'mautic_ads_intelligence_settings' => [
                'path'       => '/ads-intelligence/settings',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\SettingsController::indexAction',
            ],
            'mautic_ads_intelligence_settings_save' => [
                'path'       => '/ads-intelligence/settings/save',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\SettingsController::saveAction',
                'method'     => 'POST',
            ],

            // Creative Studio (Gemini)
            'mautic_ads_intelligence_creatives' => [
                'path'       => '/ads-intelligence/creatives',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::indexAction',
            ],
            'mautic_ads_intelligence_creatives_conversations' => [
                'path'       => '/ads-intelligence/creatives/conversations',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::listConversationsAction',
                'method'     => 'GET',
            ],
            'mautic_ads_intelligence_creatives_conversations_create' => [
                'path'       => '/ads-intelligence/creatives/conversations',
                'controller' => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::createConversationAction',
                'method'     => 'POST',
            ],
            'mautic_ads_intelligence_creatives_conversation' => [
                'path'         => '/ads-intelligence/creatives/conversations/{id}',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::getConversationAction',
                'method'       => 'GET',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_conversation_delete' => [
                'path'         => '/ads-intelligence/creatives/conversations/{id}',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::deleteConversationAction',
                'method'       => 'DELETE',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_messages' => [
                'path'         => '/ads-intelligence/creatives/conversations/{id}/messages',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::sendMessageAction',
                'method'       => 'POST',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_messages_stream' => [
                'path'         => '/ads-intelligence/creatives/conversations/{id}/messages/stream',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::streamMessageAction',
                'method'       => 'POST',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_message_status' => [
                'path'         => '/ads-intelligence/creatives/messages/{id}/status',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::messageStatusAction',
                'method'       => 'GET',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_message_replay' => [
                'path'         => '/ads-intelligence/creatives/messages/{id}/replay',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::replayMessageAction',
                'method'       => 'POST',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_message_replay_stream' => [
                'path'         => '/ads-intelligence/creatives/messages/{id}/replay/stream',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::streamReplayMessageAction',
                'method'       => 'POST',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_message_stream' => [
                'path'         => '/ads-intelligence/creatives/messages/{id}/stream',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::streamPendingMessageAction',
                'method'       => 'GET',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_message_download' => [
                'path'         => '/ads-intelligence/creatives/messages/{id}/download',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::downloadMessageAction',
                'method'       => 'GET',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_message_preview' => [
                'path'         => '/ads-intelligence/creatives/messages/{id}/preview',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::previewMessageAction',
                'method'       => 'GET',
                'requirements' => ['id' => '\\d+'],
            ],
            'mautic_ads_intelligence_creatives_message_save_asset' => [
                'path'         => '/ads-intelligence/creatives/messages/{id}/save-asset',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController::saveAssetAction',
                'method'       => 'POST',
                'requirements' => ['id' => '\\d+'],
            ],

            'mautic_ads_intelligence_contact_no_interest' => [
                'path'         => '/ads-intelligence/contact/{contactId}/no-interest',
                'controller'   => 'MauticPlugin\MauticAdsIntelligenceBundle\Controller\ContactNoInterestController::modalAction',
                'requirements' => ['contactId' => '\\d+'],
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.ads_intelligence.menu.root' => [
                'id'       => 'mautic_ads_intelligence_menu',
                'iconClass' => 'ri-bar-chart-2-line',
                'access'   => 'ads:intelligence:view',
                'priority' => 60,
                'children' => [
                    'mautic.ads_intelligence.menu.overview' => [
                        'route'    => 'mautic_ads_intelligence_overview',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 100,
                    ],
                    'mautic.ads_intelligence.menu.channels' => [
                        'route'    => 'mautic_ads_intelligence_channels',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 90,
                    ],
                    'mautic.ads_intelligence.menu.click_performance' => [
                        'route'    => 'mautic_ads_intelligence_click_performance',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 88,
                    ],
                    'mautic.ads_intelligence.menu.keyword_performance' => [
                        'route'    => 'mautic_ads_intelligence_keyword_performance',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 87,
                    ],
                    'mautic.ads_intelligence.menu.accounts' => [
                        'route'    => 'mautic_ads_intelligence_accounts',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 85,
                    ],
                    'mautic.ads_intelligence.menu.campaigns' => [
                        'route'    => 'mautic_ads_intelligence_campaigns',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 80,
                    ],
                    'mautic.ads_intelligence.menu.creatives' => [
                        'route'    => 'mautic_ads_intelligence_creatives',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 75,
                    ],
                    'mautic.ads_intelligence.menu.attributed_leads' => [
                        'route'    => 'mautic_ads_intelligence_attributed_leads',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 70,
                    ],
                    'mautic.ads_intelligence.menu.offline_conversions' => [
                        'route'    => 'mautic_ads_intelligence_offline_conversions',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 65,
                    ],
                    'mautic.ads_intelligence.menu.recommendations' => [
                        'route'    => 'mautic_ads_intelligence_recommendations',
                        'access'   => 'ads:intelligence:view_recommendations',
                        'priority' => 60,
                    ],
                    'mautic.ads_intelligence.menu.actions' => [
                        'route'    => 'mautic_ads_intelligence_actions',
                        'access'   => 'ads:intelligence:approve_actions',
                        'priority' => 50,
                    ],
                    'mautic.ads_intelligence.menu.logs' => [
                        'route'    => 'mautic_ads_intelligence_logs',
                        'access'   => 'ads:intelligence:view',
                        'priority' => 5,
                    ],
                ],
            ],
        ],
    ],

    'services' => [
        'permissions' => [
            'mautic.ads_intelligence.permissions' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Security\Permissions\AdsIntelligencePermissions::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.adsintelligence' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Integration\AdsIntelligenceIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
            'mautic.integration.adsintelligencemicrosoft' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Integration\AdsIntelligenceMicrosoftIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
        ],

        'models' => [
            'mautic.ads_intelligence.model.account' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Model\AdsAccountModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'event_dispatcher',
                    'router',
                    'translator',
                ],
            ],
            'mautic.ads_intelligence.model.campaign' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Model\AdsCampaignModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'event_dispatcher',
                    'router',
                    'translator',
                ],
            ],
            'mautic.ads_intelligence.model.recommendation' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Model\AdsRecommendationModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'event_dispatcher',
                    'router',
                    'translator',
                ],
            ],
            'mautic.ads_intelligence.model.action' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Model\AdsActionModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'event_dispatcher',
                    'router',
                    'translator',
                ],
            ],
        ],

        'other' => [
            'mautic.ads_intelligence.service.migration_runner' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\MigrationRunner::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'monolog.logger.mautic',
                ],
            ],

            // Attribution services
            'mautic.ads_intelligence.service.click_id_capture' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution\ClickIdCaptureService::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.utm_capture' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution\UtmCaptureService::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.lead_attribution' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Attribution\LeadAttributionService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.service.click_id_capture',
                    'mautic.ads_intelligence.service.utm_capture',
                    'monolog.logger.mautic',
                ],
            ],

            // Intelligence services
            'mautic.ads_intelligence.service.lead_quality' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence\LeadQualityService::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.revenue_attribution' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence\RevenueAttributionService::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.funnel_metrics' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence\FunnelMetricsService::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.lead_quality',
                    'mautic.ads_intelligence.service.revenue_attribution',
                ],
            ],
            'mautic.ads_intelligence.service.channel_comparison' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence\ChannelComparisonService::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.funnel_metrics',
                ],
            ],
            'mautic.ads_intelligence.service.performance_data' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Intelligence\PerformanceDataService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.provider.google',
                    'mautic.ads_intelligence.provider.microsoft',
                    'mautic.ads_intelligence.provider.linkedin',
                ],
            ],

            // Providers
            'mautic.ads_intelligence.provider.google' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Provider\GoogleAdsProvider::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.helper.encryption',
                    'mautic.native.connector',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.provider.microsoft' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Provider\MicrosoftAdsProvider::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.helper.encryption',
                    'mautic.native.connector',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.provider.linkedin' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Provider\LinkedInAdsProvider::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.account_sync' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsAccountSyncService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.provider.google',
                    'mautic.ads_intelligence.provider.microsoft',
                    'mautic.ads_intelligence.service.provider_status',
                ],
            ],
            'mautic.ads_intelligence.service.campaign_sync' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsCampaignSyncService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.provider.google',
                    'mautic.ads_intelligence.provider.microsoft',
                    'mautic.ads_intelligence.service.provider_status',
                ],
            ],
            'mautic.ads_intelligence.service.provider_status' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\ProviderStatusService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.ads_intelligence.service.campaign_create' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsCampaignCreateService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.provider.google',
                ],
            ],
            'mautic.ads_intelligence.service.keyword_management' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsKeywordManagementService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.provider.google',
                    'mautic.ads_intelligence.provider.microsoft',
                ],
            ],

            // AI services
            'mautic.ads_intelligence.service.ai_analyzer' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\AiAnalyzerService::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.prompt_builder',
                    'mautic.ads_intelligence.service.recommendation_parser',
                    'mautic.ads_intelligence.service.ai_safety_validator',
                    'mautic.ads_intelligence.service.gemini_settings',
                    'mautic.native.connector',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.service.gemini_settings' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\GeminiSettingsService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.helper.encryption',
                ],
            ],
            'mautic.ads_intelligence.service.gemini_creative_client' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\GeminiCreativeClient::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.gemini_settings',
                    'mautic.native.connector',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.service.creative_media' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeMediaService::class,
                'arguments' => [
                    'mautic.helper.paths',
                    'mautic.helper.core_parameters',
                    'mautic.asset.model.asset',
                ],
            ],
            'mautic.ads_intelligence.service.creative_conversation' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeConversationService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.service.gemini_creative_client',
                    'mautic.ads_intelligence.service.creative_media',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.service.creative_generation_stream' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\CreativeGenerationStreamService::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.creative_conversation',
                ],
            ],
            'mautic.ads_intelligence.service.prompt_builder' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\PromptBuilderService::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.recommendation_parser' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\RecommendationParserService::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.ai_safety_validator' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\AiSafetyValidator::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.negative_keyword_recommendation' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\AI\NegativeKeywordRecommendationService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.service.ai_analyzer',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.command.generate_recommendations' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Command\GenerateRecommendationsCommand::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.negative_keyword_recommendation',
                ],
                'tag'       => 'console.command',
            ],

            // Execution services
            'mautic.ads_intelligence.service.approval' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution\ApprovalService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.service.audit',
                ],
            ],
            'mautic.ads_intelligence.service.action_executor' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution\ActionExecutorService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.service.guardrail',
                    'mautic.ads_intelligence.service.audit',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.service.guardrail' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution\GuardrailService::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.audit' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Execution\AuditService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],

            // Conversion services
            'mautic.ads_intelligence.service.offline_conversion_mapper' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionMapper::class,
                'arguments' => [],
            ],
            'mautic.ads_intelligence.service.offline_conversion_queue' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionQueueService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.service.offline_conversion_mapper',
                ],
            ],
            'mautic.ads_intelligence.service.offline_conversion_settings' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionSettingsService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.helper.encryption',
                ],
            ],
            'mautic.ads_intelligence.service.offline_conversion_stage_enqueue' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionStageEnqueueService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.service.offline_conversion_queue',
                    'mautic.ads_intelligence.service.offline_conversion_settings',
                ],
            ],
            'mautic.ads_intelligence.service.offline_conversion_sync' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionSyncService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.ads_intelligence.service.offline_conversion_queue',
                    'mautic.ads_intelligence.service.audit',
                    'monolog.logger.mautic',
                    'mautic.ads_intelligence.service.offline_conversion_settings',
                    'mautic.ads_intelligence.provider.google',
                    'mautic.ads_intelligence.provider.microsoft',
                ],
            ],
        ],

        'events' => [
            'mautic.ads_intelligence.listener.plugin_config' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\EventListener\PluginConfigSubscriber::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.migration_runner',
                    'monolog.logger.mautic',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.encryption',
                ],
            ],
            'mautic.ads_intelligence.listener.lead' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.lead_attribution',
                    'request_stack',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.listener.form' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\EventListener\FormSubscriber::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.lead_attribution',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.listener.page' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\EventListener\PageSubscriber::class,
                'arguments' => [
                    'mautic.ads_intelligence.service.lead_attribution',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.ads_intelligence.listener.contact_button' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\EventListener\ContactButtonSubscriber::class,
                'arguments' => [
                    'router',
                    'translator',
                    'mautic.security',
                ],
            ],
            'mautic.ads_intelligence.listener.contact_no_interest_reason' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\EventListener\ContactNoInterestReasonSubscriber::class,
                'arguments' => [
                    'twig',
                ],
            ],
            'mautic.ads_intelligence.listener.creative_assets' => [
                'class'     => \MauticPlugin\MauticAdsIntelligenceBundle\EventListener\CreativeAssetsSubscriber::class,
                'arguments' => [
                    'request_stack',
                ],
            ],
        ],
    ],

    'parameters' => [],
];
