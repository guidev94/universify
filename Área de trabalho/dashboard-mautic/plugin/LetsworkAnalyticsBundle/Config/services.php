<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('MauticPlugin\\LetsworkAnalyticsBundle\\', '../')
        ->exclude('../{Config,Resources,Tests,Translations,Security}');

    $services->alias('letswork.analytics.model', 'MauticPlugin\LetsworkAnalyticsBundle\Model\AnalyticsModel');
};
