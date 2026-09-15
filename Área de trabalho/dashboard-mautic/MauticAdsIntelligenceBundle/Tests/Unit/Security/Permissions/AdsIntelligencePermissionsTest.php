<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Security\Permissions;

use Mautic\UserBundle\Form\Type\PermissionListType;
use MauticPlugin\MauticAdsIntelligenceBundle\Security\Permissions\AdsIntelligencePermissions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

class AdsIntelligencePermissionsTest extends TestCase
{
    public function testViewPermissionIsSupported(): void
    {
        $permissions = new AdsIntelligencePermissions([]);

        self::assertSame('ads', $permissions->getName());
        self::assertTrue($permissions->isSupported('intelligence', 'view'));
        self::assertSame(2, $permissions->getValue('intelligence', 'view'));
    }

    public function testPermissionServiceIsRegisteredInPluginConfig(): void
    {
        $config  = require dirname(__DIR__, 4).'/Config/config.php';
        $service = $config['services']['permissions']['mautic.ads_intelligence.permissions'] ?? null;

        self::assertIsArray($service);
        self::assertSame(AdsIntelligencePermissions::class, $service['class']);
        self::assertSame(['mautic.helper.core_parameters'], $service['arguments']);
    }

    public function testRoleFormUsesTheSupportedCustomPermissions(): void
    {
        $permissions = new AdsIntelligencePermissions([]);
        $builder     = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'ads:intelligence',
                PermissionListType::class,
                self::callback(static function (array $options): bool {
                    return 'ads' === $options['bundle']
                        && 'intelligence' === $options['level']
                        && 'view' === $options['choices']['mautic.ads_intelligence.permission.view'];
                }),
            )
            ->willReturnSelf();

        $permissions->buildForm($builder, [], []);
    }
}
