<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Tests\Unit\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticAdsIntelligenceBundle\Controller\CreativeStudioController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CreativeStudioControllerPermissionTest extends TestCase
{
    public function testCreativeAccessUsesMauticPermissionService(): void
    {
        $security = $this->createMock(CorePermissions::class);
        $security->expects(self::once())
            ->method('isGranted')
            ->with('ads:intelligence:view')
            ->willReturn(true);

        $controller = $this->createController($security);

        $this->invokeAccessAssertion($controller);
        self::assertTrue(true);
    }

    public function testCreativeAccessRemainsDeniedWithoutMauticPermission(): void
    {
        $security = $this->createMock(CorePermissions::class);
        $security->method('isGranted')->with('ads:intelligence:view')->willReturn(false);

        $controller = $this->createController($security);

        $this->expectException(AccessDeniedException::class);
        $this->invokeAccessAssertion($controller);
    }

    public function testControllerDoesNotUseSymfonyRoleAuthorizationForMauticPermission(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/Controller/CreativeStudioController.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('denyAccessUnlessGranted', $source);
    }

    public function testEventStreamUsesBackwardCompatibleStreamedResponse(): void
    {
        $controller = (new \ReflectionClass(CreativeStudioController::class))->newInstanceWithoutConstructor();
        $method     = new \ReflectionMethod($controller, 'createEventStream');
        $response   = $method->invoke($controller, static function (): void {
        });

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('text/event-stream; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        self::assertStringContainsString('no-transform', (string) $response->headers->get('Cache-Control'));
        self::assertSame('no', $response->headers->get('X-Accel-Buffering'));
    }

    public function testServerEventFormatSupportsMultilineData(): void
    {
        $controller = (new \ReflectionClass(CreativeStudioController::class))->newInstanceWithoutConstructor();
        $method     = new \ReflectionMethod($controller, 'formatServerEvent');

        self::assertSame(
            "event: progress\ndata: first\ndata: second\n\n",
            $method->invoke($controller, 'progress', "first\nsecond"),
        );
    }

    public function testServerEventRejectsInvalidEventName(): void
    {
        $controller = (new \ReflectionClass(CreativeStudioController::class))->newInstanceWithoutConstructor();
        $method     = new \ReflectionMethod($controller, 'formatServerEvent');

        $this->expectException(\InvalidArgumentException::class);
        $method->invoke($controller, "progress\ndata: injected", '{}');
    }

    private function createController(CorePermissions $security): CreativeStudioController
    {
        $controller = (new \ReflectionClass(CreativeStudioController::class))->newInstanceWithoutConstructor();
        $property   = new \ReflectionProperty(CommonController::class, 'security');
        $property->setValue($controller, $security);

        return $controller;
    }

    private function invokeAccessAssertion(CreativeStudioController $controller): void
    {
        $method = new \ReflectionMethod($controller, 'assertCanViewCreatives');
        $method->invoke($controller);
    }
}
