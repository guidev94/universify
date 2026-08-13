<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Security\Permissions;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class AdsIntelligencePermissions extends AbstractPermissions
{
    /**
     * @param array<string, mixed>|CoreParametersHelper $params
     */
    public function __construct(array|CoreParametersHelper $params)
    {
        parent::__construct($params instanceof CoreParametersHelper ? $params->all() : $params);

        $this->addCustomPermission('intelligence', [
            'view'                 => 2,
            'manage_accounts'      => 4,
            'view_recommendations' => 8,
            'approve_actions'      => 16,
            'execute_actions'      => 32,
            'manage_settings'      => 64,
        ]);
    }

    public function getName(): string
    {
        return 'ads';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addCustomFormFields(
            'ads',
            'intelligence',
            $builder,
            'mautic.ads_intelligence.permissions.intelligence',
            [
                'mautic.ads_intelligence.permission.view'                 => 'view',
                'mautic.ads_intelligence.permission.manage_accounts'      => 'manage_accounts',
                'mautic.ads_intelligence.permission.view_recommendations' => 'view_recommendations',
                'mautic.ads_intelligence.permission.approve_actions'      => 'approve_actions',
                'mautic.ads_intelligence.permission.execute_actions'      => 'execute_actions',
                'mautic.ads_intelligence.permission.manage_settings'      => 'manage_settings',
            ],
            $data,
        );
    }
}
