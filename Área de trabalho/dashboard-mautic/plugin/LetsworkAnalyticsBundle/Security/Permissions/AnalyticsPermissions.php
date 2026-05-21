<?php

namespace MauticPlugin\LetsworkAnalyticsBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class AnalyticsPermissions extends AbstractPermissions
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addManagePermission('analytics');
    }

    public function getName(): string
    {
        return 'letsworkAnalytics';
    }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addManageFormFields('letsworkAnalytics', 'analytics', $builder, $data);
    }
}
