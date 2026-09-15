<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Tag;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsConversionQueue;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\GoogleAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\MicrosoftAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionSettingsService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionStageEnqueueService;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion\OfflineConversionSyncService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OfflineConversionController extends CommonController
{
    private const CSV_EXPORT_PROVIDERS = ['google', 'microsoft'];

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine.orm.entity_manager' => EntityManagerInterface::class,
            'mautic.ads_intelligence.service.offline_conversion_settings' => OfflineConversionSettingsService::class,
            'mautic.ads_intelligence.service.offline_conversion_stage_enqueue' => OfflineConversionStageEnqueueService::class,
            'mautic.ads_intelligence.service.offline_conversion_sync' => OfflineConversionSyncService::class,
            'mautic.ads_intelligence.provider.google' => GoogleAdsProvider::class,
            'mautic.ads_intelligence.provider.microsoft' => MicrosoftAdsProvider::class,
        ]);
    }

    public function indexAction(Request $request): Response
    {
        $em = $this->getEntityManager();

        return $this->delegateView([
            'viewParameters'  => [
                'currentRoute' => 'mautic_ads_intelligence_offline_conversions',
                'settings'     => $this->getSettingsService()->getSettings(),
                'tags'         => $em->getRepository(Tag::class)->findBy([], ['tag' => 'ASC']),
                'summary'      => $this->getQueueSummary(),
                'googleConversionActions' => $this->getGoogleConversionActions(),
                'microsoftConversionGoals' => $this->getMicrosoftConversionGoals(),
                'googleAccounts' => $em->getRepository(AdsAccount::class)->findBy(['provider' => 'google'], ['name' => 'ASC']),
                'microsoftAccounts' => $em->getRepository(AdsAccount::class)->findBy(['provider' => 'microsoft'], ['name' => 'ASC']),
            ],
            'contentTemplate' => '@MauticAdsIntelligence/OfflineConversion/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_offline_conversions',
                'mauticContent' => 'adsIntelligenceOfflineConversions',
            ],
        ]);
    }

    public function saveAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_offline_conversions', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->getSettingsService()->save(
            array_map('intval', (array) $request->request->all('tag_ids')),
            (string) $request->request->get('conversion_name', OfflineConversionSettingsService::DEFAULT_CONVERSION_NAME),
            (float) $request->request->get('conversion_value', 0),
            (string) $request->request->get('currency', 'BRL'),
            (string) $request->request->get('google_conversion_action', ''),
            (string) $request->request->get('bing_feed_username', ''),
            (string) $request->request->get('bing_feed_password', ''),
        );

        $this->addFlash('notice', 'mautic.ads_intelligence.offline_conversions.saved');

        return $this->redirectToRoute('mautic_ads_intelligence_offline_conversions');
    }

    public function enqueueAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_offline_conversions_enqueue', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $result = $this->container->get('mautic.ads_intelligence.service.offline_conversion_stage_enqueue')->enqueueConvertedLeads();
        $this->addFlash('notice', sprintf('Leads encontrados: %d. Conversões enfileiradas: %d. Ignoradas: %d.', $result['matched'], $result['queued'], $result['skipped']));

        return $this->redirectToRoute('mautic_ads_intelligence_offline_conversions');
    }

    public function syncAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_offline_conversions_sync', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $result = $this->container->get('mautic.ads_intelligence.service.offline_conversion_sync')->sync();
        $this->addFlash('notice', sprintf('Processadas: %d. Enviadas: %d. Falhas: %d.', $result['processed'], $result['sent'], $result['failed']));

        if ($result['failed'] > 0 && !empty($result['errors'])) {
            $this->addFlash('error', sprintf('Erros no envio: %s', implode(' | ', array_slice($result['errors'], 0, 3))));
        }

        return $this->redirectToRoute('mautic_ads_intelligence_offline_conversions');
    }

    public function downloadAction(string $provider): Response
    {
        if (!in_array($provider, self::CSV_EXPORT_PROVIDERS, true)) {
            throw $this->createNotFoundException('Invalid offline conversion provider.');
        }

        $conversions = $this->getPendingConversions($provider);

        $googleConversionActionNames = 'google' === $provider ? $this->getGoogleConversionActionNameMap() : [];
        $csv                         = $this->buildOfflineConversionCsv($provider, $conversions, $googleConversionActionNames);
        $filename                    = sprintf(
            'offline-conversions-%s-%s.csv',
            'microsoft' === $provider ? 'bing' : 'google',
            (new \DateTimeImmutable())->format('Ymd-His')
        );

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Cache-Control'       => 'no-store, max-age=0',
        ]);
    }

    public function bingFeedAction(Request $request): Response
    {
        $credentials = $this->getSettingsService()->getCsvFeedCredentials();
        if (!$this->isValidCsvFeedRequest($request, $credentials)) {
            return $this->createCsvFeedUnauthorizedResponse();
        }

        $csv = $this->buildOfflineConversionCsv('microsoft', $this->getPendingConversions('microsoft'), []);

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="offline-conversions-bing.csv"',
            'Cache-Control'       => 'no-store, max-age=0',
        ]);
    }

    public function googleFeedAction(Request $request): Response
    {
        $credentials = $this->getSettingsService()->getCsvFeedCredentials();
        if (!$this->isValidCsvFeedRequest($request, $credentials)) {
            return $this->createCsvFeedUnauthorizedResponse();
        }

        $csv = $this->buildOfflineConversionCsv('google', $this->getPendingConversions('google'), $this->getGoogleConversionActionNameMap());

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="offline-conversions-google.csv"',
            'Cache-Control'       => 'no-store, max-age=0',
        ]);
    }

    public function createGoogleActionAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_offline_conversions_create_action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $accountId = (int) $request->request->get('account_id');
        $name = trim((string) $request->request->get('action_name', 'Oportunidade/Cliente CRM'));
        $name = '' !== $name ? $name : 'Oportunidade/Cliente CRM';

        $account = $this->getEntityManager()->getRepository(AdsAccount::class)->findOneBy([
            'id'       => $accountId,
            'provider' => 'google',
        ]);

        if (!$account instanceof AdsAccount) {
            $this->addFlash('error', 'Conta Google Ads inválida.');

            return $this->redirectToRoute('mautic_ads_intelligence_offline_conversions');
        }

        try {
            $created = $this->container->get('mautic.ads_intelligence.provider.google')->createOfflineConversionAction($account, $name);

            $settings = $this->getSettingsService()->getSettings();
            $this->getSettingsService()->save(
                $settings['tag_ids'],
                $settings['conversion_name'],
                $settings['conversion_value'],
                $settings['currency'],
                $created['resourceName'],
            );

            $this->addFlash('notice', sprintf('Conversion action "%s" criada e selecionada (%s).', $created['name'], $created['resourceName']));
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Falha ao criar conversion action: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('mautic_ads_intelligence_offline_conversions');
    }

    public function createMicrosoftActionAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_offline_conversions_create_ms_action', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $accountId = (int) $request->request->get('account_id');
        $name = trim((string) $request->request->get('action_name', 'Oportunidade/Cliente CRM'));
        $name = '' !== $name ? $name : 'Oportunidade/Cliente CRM';

        $account = $this->getEntityManager()->getRepository(AdsAccount::class)->findOneBy([
            'id'       => $accountId,
            'provider' => 'microsoft',
        ]);

        if (!$account instanceof AdsAccount) {
            $this->addFlash('error', 'Conta Microsoft Advertising inválida.');

            return $this->redirectToRoute('mautic_ads_intelligence_offline_conversions');
        }

        try {
            $created = $this->container->get('mautic.ads_intelligence.provider.microsoft')->createOfflineConversionGoal($account, $name);

            $settings = $this->getSettingsService()->getSettings();
            $this->getSettingsService()->save(
                $settings['tag_ids'],
                $created['name'],
                $settings['conversion_value'],
                $settings['currency'],
                $settings['google_conversion_action'],
            );

            $this->addFlash('notice', sprintf('Conversion goal "%s" criado e selecionado (ID %s).', $created['name'], $created['id']));
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Falha ao criar conversion goal: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('mautic_ads_intelligence_offline_conversions');
    }

    /**
     * @return array<string, int>
     */
    private function getQueueSummary(): array
    {
        $rows = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('status, COUNT(*) total')
            ->from(MAUTIC_TABLE_PREFIX.'ads_conversion_queue')
            ->groupBy('status')
            ->executeQuery()
            ->fetchAllAssociative();

        $summary = ['pending' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $summary[(string) $row['status']] = (int) $row['total'];
        }

        return $summary;
    }

    private function getSettingsService(): OfflineConversionSettingsService
    {
        return $this->container->get('mautic.ads_intelligence.service.offline_conversion_settings');
    }

    /**
     * @return AdsConversionQueue[]
     */
    private function getPendingConversions(string $provider): array
    {
        return $this->getEntityManager()->getRepository(AdsConversionQueue::class)->findBy([
            'provider' => $provider,
            'status'   => 'pending',
        ], ['createdAt' => 'ASC']);
    }

    /**
     * @param array{username: string, password: string} $credentials
     */
    private function isValidCsvFeedRequest(Request $request, array $credentials): bool
    {
        if ('' === $credentials['username'] || '' === $credentials['password']) {
            return false;
        }

        return hash_equals($credentials['username'], (string) $request->getUser())
            && hash_equals($credentials['password'], (string) $request->getPassword());
    }

    private function createCsvFeedUnauthorizedResponse(): Response
    {
        return new Response('Authentication required.', Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => 'Basic realm="Ads Intelligence Offline Conversions"',
            'Cache-Control'    => 'no-store, max-age=0',
        ]);
    }

    /**
     * @param AdsConversionQueue[] $conversions
     * @param array<string, string> $googleConversionActionNames
     */
    private function buildOfflineConversionCsv(string $provider, array $conversions, array $googleConversionActionNames): string
    {
        $handle = fopen('php://temp', 'r+');
        if (false === $handle) {
            throw new \RuntimeException('Unable to create temporary CSV stream.');
        }

        if ('google' === $provider) {
            $this->writeGoogleOfflineConversionCsvTemplate($handle);
        } elseif ('microsoft' === $provider) {
            $this->writeMicrosoftOfflineConversionCsvTemplate($handle);
        } else {
            fputcsv($handle, $this->getOfflineConversionCsvHeaders($provider));
        }

        foreach ($conversions as $conversion) {
            $row = 'google' === $provider
                ? $this->buildGoogleOfflineConversionCsvRow($conversion, $googleConversionActionNames)
                : $this->buildMicrosoftOfflineConversionCsvRow($conversion);

            if (null === $row) {
                continue;
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return false === $csv ? '' : $csv;
    }

    /**
     * @return array<int, string>
     */
    private function getOfflineConversionCsvHeaders(string $provider): array
    {
        if ('google' === $provider) {
            return ['Email', 'Phone Number', 'Conversion Name', 'Conversion Time', 'Conversion Value', 'Conversion Currency', 'Ad User Data', 'Ad Personalization'];
        }

        return ['Conversion Name', 'Conversion Time', 'Conversion Value', 'Conversion Currency', 'Microsoft Click ID', 'Hashed Email Address', 'Hashed Phone Number', ''];
    }

    /**
     * @param resource $handle
     */
    private function writeGoogleOfflineConversionCsvTemplate($handle): void
    {
        fwrite($handle, implode("\n", [
            '### INSTRUCTIONS ####',
            '"# IMPORTANT: Remember to set the TimeZone value in the ""parameters"" row and/or in your Conversion Time column"',
            '"# For instructions on how to setup your data, visit http://goo.gl/T1C5Ov"',
            ',,,,,',
            '### TEMPLATE ###',
            'Parameters:TimeZone=+0000',
            'Email,Phone Number,Conversion Name,Conversion Time,Conversion Value,Conversion Currency,Ad User Data,Ad Personalization',
        ])."\n");
    }

    /**
     * @param resource $handle
     */
    private function writeMicrosoftOfflineConversionCsvTemplate($handle): void
    {
        fwrite($handle, implode("\n", [
            '### INSTRUCTIONS ###,,,,,,,',
            '"# Important: Remember to change the TimeZone value (GMT offset) in the ""parameters"" row",,,,,,,',
            '"# TimeZone values should be entered in HHMM format (e.g. New York is -0500 and Berlin is +0100. If you use Greenwich Mean Time, simply enter +0000). ",,,,,,,',
            '"# Make sure you do not include additional columns. For more instructions on how to use this tempate, visit https://aka.ms/dk8o1y ",,,,,,,',
            '"# If you are using regular Offline Conversions (not Enhanced), you can leave the last two columns empty. ",,,,,,,',
            '"# If you are using Enhanced Offline Conversions, you can leave the Microsoft Click ID field empty. You can add both email address and phone number or just one of the two.",,,,,,,',
            '"# Remember to follow the hashing rules before adding the customer information to this document. Learn more here: https://help.ads.microsoft.com/#apex/ads/en/60178/-1",,,,,,,',
            ',,,,,,,',
            '### TEMPLATE ###,,,,,,,',
            'Parameters:TimeZone=+0000,,,,,,,',
            'Conversion Name,Conversion Time,Conversion Value,Conversion Currency,Microsoft Click ID,Hashed Email Address,Hashed Phone Number,',
        ])."\n");
    }

    /**
     * @param array<string, string> $googleConversionActionNames
     *
     * @return array<int, string>|null
     */
    private function buildGoogleOfflineConversionCsvRow(AdsConversionQueue $conversion, array $googleConversionActionNames): ?array
    {
        $payload        = $conversion->getPayload() ?? [];
        $resourceName   = (string) ($payload['google_conversion_action'] ?? '');
        $conversionName = $googleConversionActionNames[$resourceName] ?? $conversion->getConversionName();
        $hashedEmail    = $this->getUsableHashedValue($conversion->getHashedEmail());
        $hashedPhone    = $this->getUsableHashedValue($conversion->getHashedPhone());

        if (null === $hashedEmail && null === $hashedPhone) {
            return null;
        }

        return [
            (string) ($hashedEmail ?? ''),
            (string) ($hashedPhone ?? ''),
            $conversionName,
            \DateTimeImmutable::createFromInterface($conversion->getConversionTime())
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s'),
            $this->formatCsvDecimal($conversion->getConversionValue()),
            $conversion->getConversionCurrency(),
            '',
            '',
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function buildMicrosoftOfflineConversionCsvRow(AdsConversionQueue $conversion): ?array
    {
        $clickId     = $this->getCompatibleClickId($conversion, ['msclkid', 'microsoftclickid'], 'msclkid');
        $hashedEmail = $this->getUsableHashedValue($conversion->getHashedEmail());
        $hashedPhone = $this->getUsableHashedValue($conversion->getHashedPhone());

        if ('' === $clickId && null === $hashedEmail && null === $hashedPhone) {
            return null;
        }

        return [
            $conversion->getConversionName(),
            \DateTimeImmutable::createFromInterface($conversion->getConversionTime())
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s'),
            $this->formatCsvDecimal($conversion->getConversionValue()),
            $conversion->getConversionCurrency(),
            $clickId,
            (string) ($hashedEmail ?? ''),
            (string) ($hashedPhone ?? ''),
            '',
        ];
    }

    private function getUsableHashedValue(?string $value): ?string
    {
        $value = trim((string) $value);
        if ('' === $value || hash('sha256', '') === $value) {
            return null;
        }

        return $value;
    }

    /**
     * @param array<int, string> $compatibleTypes
     */
    private function getCompatibleClickId(AdsConversionQueue $conversion, array $compatibleTypes, string $defaultType): string
    {
        $clickId = (string) ($conversion->getClickId() ?? '');
        if ('' === $clickId) {
            return '';
        }

        $clickIdType = strtolower((string) ($conversion->getClickIdType() ?? $defaultType));

        return in_array($clickIdType, $compatibleTypes, true) ? $clickId : '';
    }

    private function formatCsvDecimal(?float $value): string
    {
        return number_format($value ?? 0.0, 2, '.', '');
    }

    /**
     * @return array<string, string>
     */
    private function getGoogleConversionActionNameMap(): array
    {
        $names = [];
        foreach ($this->getGoogleConversionActions() as $action) {
            $resourceName = $action['resourceName'];
            if ('' === $resourceName) {
                continue;
            }

            $names[$resourceName] = $action['name'];
        }

        return $names;
    }

    /**
     * @return array<int, array{account: string, resourceName: string, name: string, status: string, category: string, type: string}>
     */
    private function getGoogleConversionActions(): array
    {
        $provider = $this->container->get('mautic.ads_intelligence.provider.google');
        $accounts = $this->getEntityManager()->getRepository(AdsAccount::class)->findBy([
            'provider' => 'google',
        ], ['name' => 'ASC']);

        $actions = [];
        foreach ($accounts as $account) {
            if (!$account instanceof AdsAccount) {
                continue;
            }

            try {
                $actions = array_merge($actions, $provider->fetchConversionActions($account));
            } catch (\Throwable) {
                continue;
            }
        }

        return $actions;
    }

    /**
     * @return array<int, array{account: string, id: string, name: string, status: string}>
     */
    private function getMicrosoftConversionGoals(): array
    {
        $provider = $this->container->get('mautic.ads_intelligence.provider.microsoft');
        $accounts = $this->getEntityManager()->getRepository(AdsAccount::class)->findBy([
            'provider' => 'microsoft',
        ], ['name' => 'ASC']);

        $goals = [];
        foreach ($accounts as $account) {
            if (!$account instanceof AdsAccount) {
                continue;
            }

            try {
                $goals = array_merge($goals, $provider->fetchConversionGoals($account));
            } catch (\Throwable) {
                continue;
            }
        }

        return $goals;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
