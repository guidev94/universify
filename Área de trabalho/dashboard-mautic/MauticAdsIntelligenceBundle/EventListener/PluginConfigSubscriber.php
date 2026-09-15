<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\MauticAdsIntelligenceBundle\Integration\AdsIntelligenceIntegration;
use MauticPlugin\MauticAdsIntelligenceBundle\Integration\AdsIntelligenceMicrosoftIntegration;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\MigrationRunner;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PluginConfigSubscriber implements EventSubscriberInterface
{
    private bool $apiKeysNormalized = false;

    public function __construct(
        private MigrationRunner $migrationRunner,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private EncryptionHelper $encryptionHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 512],
            PluginEvents::ON_PLUGIN_INSTALL => ['onPluginInstall', 0],
            PluginEvents::ON_PLUGIN_UPDATE => ['onPluginUpdate', 0],
            PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE => ['onIntegrationConfigSave', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $this->apiKeysNormalized) {
            return;
        }

        $this->apiKeysNormalized = true;
        $this->normalizeStoredApiKeys();
    }

    public function onPluginInstall(PluginInstallEvent $event): void
    {
        if (!$this->isAdsIntelligencePlugin($event->getPlugin()->getBundle())) {
            return;
        }

        $this->migrationRunner->run();
        $this->logger->info('Mautic Ads Intelligence schema checked after plugin install.');
    }

    public function onPluginUpdate(PluginUpdateEvent $event): void
    {
        if (!$this->isAdsIntelligencePlugin($event->getPlugin()->getBundle())) {
            return;
        }

        $this->migrationRunner->run();
        $this->logger->info('Mautic Ads Intelligence schema checked after plugin update.');
    }

    public function onIntegrationConfigSave(PluginIntegrationEvent $event): void
    {
        if (!in_array($event->getIntegrationName(), [AdsIntelligenceIntegration::NAME, AdsIntelligenceMicrosoftIntegration::NAME], true)) {
            return;
        }

        if (!$event->getIntegration()->getIntegrationSettings()->isPublished()) {
            return;
        }

        $this->migrationRunner->run();
        if ($event->getIntegration() instanceof AdsIntelligenceIntegration) {
            $event->getIntegration()->syncGoogleProviderCredential();
        }

        if ($event->getIntegration() instanceof AdsIntelligenceMicrosoftIntegration) {
            $event->getIntegration()->syncMicrosoftProviderCredential();
        }

        $this->logger->info('Mautic Ads Intelligence schema checked after plugin activation.');
    }

    private function normalizeStoredApiKeys(): void
    {
        try {
            $repository = $this->entityManager->getRepository(Integration::class);
            $changed    = false;

            foreach ([AdsIntelligenceIntegration::NAME, AdsIntelligenceMicrosoftIntegration::NAME] as $name) {
                $integration = $repository->findOneBy(['name' => $name]);
                if (!$integration instanceof Integration || !is_array($integration->getApiKeys())) {
                    continue;
                }

                $apiKeys = $integration->getApiKeys();
                foreach ($apiKeys as $key => $value) {
                    if (is_string($value) || null === $value) {
                        continue;
                    }

                    if (!is_scalar($value)) {
                        $encoded = json_encode($value);
                        if (false === $encoded) {
                            unset($apiKeys[$key]);
                            $changed = true;
                            continue;
                        }
                        $value = $encoded;
                    }

                    $apiKeys[$key] = $this->encryptionHelper->encrypt((string) $value);
                    $changed       = true;
                }

                $integration->setApiKeys($apiKeys);
                $this->entityManager->persist($integration);
            }

            if ($changed) {
                $this->entityManager->flush();
                $this->logger->info('Mautic Ads Intelligence normalized stored integration API keys.');
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Mautic Ads Intelligence could not normalize integration API keys: '.$e->getMessage());
        }
    }

    private function isAdsIntelligencePlugin(string $bundle): bool
    {
        return 'MauticAdsIntelligenceBundle' === $bundle;
    }
}
