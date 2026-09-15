<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsCampaign;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsMetricDaily;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\GoogleAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\LinkedInAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\MicrosoftAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Provider\OpenAiAdsProvider;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\ProviderStatusService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:ads-intelligence:sync-metrics',
    description: 'Synchronizes daily search-query metrics from all connected Ads accounts'
)]
class SyncMetricsCommand extends Command
{
    /**
     * Statuses that mean "this account should be synced". Providers historically
     * wrote different values, so both are accepted.
     */
    private const SYNCABLE_STATUSES = ['active', 'enabled'];

    /** @var array<string, int|null> */
    private array $campaignIdCache = [];

    /** @var array<string, int|null> */
    private array $keywordIdCache = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GoogleAdsProvider $googleAdsProvider,
        private readonly MicrosoftAdsProvider $microsoftAdsProvider,
        private readonly LinkedInAdsProvider $linkedInAdsProvider,
        private readonly OpenAiAdsProvider $openAiAdsProvider,
        private readonly ProviderStatusService $providerStatusService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Start date (e.g. "-7 days" or "2026-01-01")', '-7 days')
            ->addOption('to', null, InputOption::VALUE_OPTIONAL, 'End date (e.g. "today" or "2026-01-07")', 'today')
            ->addOption('provider', null, InputOption::VALUE_OPTIONAL, 'Sync only this provider (google, microsoft, linkedin, openai)')
            ->addOption('account', null, InputOption::VALUE_OPTIONAL, 'Sync only this internal account ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $from = new \DateTime((string) $input->getOption('from'));
            $to   = new \DateTime((string) $input->getOption('to'));
        } catch (\Throwable $e) {
            $output->writeln('<error>Invalid date format: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $output->writeln(sprintf(
            '<info>Syncing metrics from %s to %s...</info>',
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        ));

        $accounts = $this->findAccounts(
            $input->getOption('provider'),
            $input->getOption('account')
        );

        if ([] === $accounts) {
            $output->writeln('<comment>No syncable accounts found.</comment>');

            return Command::SUCCESS;
        }

        $totalMetrics = 0;
        $processed    = 0;
        $errors       = [];

        foreach ($accounts as $account) {
            $providerName = $account->getProvider();

            if (!$this->providerStatusService->isProviderEnabled($providerName)) {
                $output->writeln(sprintf('  <comment>Skipping %s (provider %s is disabled)</comment>', $account->getName(), $providerName));
                continue;
            }

            $provider = $this->getProvider($providerName);
            if (null === $provider) {
                $output->writeln(sprintf('  <comment>Skipping %s (no provider implementation for %s)</comment>', $account->getName(), $providerName));
                continue;
            }

            ++$processed;
            $output->write(sprintf('  [%s] %s... ', strtoupper($providerName), $account->getName()));

            try {
                $metrics = $provider->syncMetrics($account, $from, $to);
            } catch (\Throwable $e) {
                $output->writeln(sprintf('<error>ERROR: %s</error>', $e->getMessage()));
                $errors[] = sprintf('%s (%s): %s', $account->getName(), $providerName, $e->getMessage());
                continue;
            }

            if ([] === $metrics) {
                $output->writeln('<comment>0 metrics</comment>');
                continue;
            }

            $upserted = 0;
            foreach ($metrics as $metric) {
                if ($this->upsertMetric($account, $metric)) {
                    ++$upserted;
                }
            }

            $this->entityManager->flush();
            $totalMetrics += $upserted;
            $output->writeln(sprintf('<info>%d metrics</info>', $upserted));
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Done. %d metric row(s) upserted across %d account(s).</info>',
            $totalMetrics,
            $processed
        ));

        if ([] !== $errors) {
            $output->writeln(sprintf('<comment>%d error(s):</comment>', count($errors)));
            foreach ($errors as $error) {
                $output->writeln(sprintf('  - %s', $error));
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, AdsAccount>
     */
    private function findAccounts(mixed $providerFilter, mixed $accountFilter): array
    {
        $qb = $this->entityManager->getRepository(AdsAccount::class)->createQueryBuilder('a');
        $qb->where($qb->expr()->in('a.status', ':statuses'))
            ->setParameter('statuses', self::SYNCABLE_STATUSES)
            ->orderBy('a.provider', 'ASC')
            ->addOrderBy('a.name', 'ASC');

        if (is_string($providerFilter) && '' !== $providerFilter) {
            $qb->andWhere('a.provider = :provider')->setParameter('provider', $providerFilter);
        }

        if (null !== $accountFilter && '' !== $accountFilter) {
            $qb->andWhere('a.id = :accountId')->setParameter('accountId', (int) $accountFilter);
        }

        /** @var array<int, AdsAccount> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    private function getProvider(string $provider): GoogleAdsProvider|MicrosoftAdsProvider|LinkedInAdsProvider|OpenAiAdsProvider|null
    {
        return match ($provider) {
            'google'    => $this->googleAdsProvider,
            'microsoft' => $this->microsoftAdsProvider,
            'linkedin'  => $this->linkedInAdsProvider,
            'openai'    => $this->openAiAdsProvider,
            default     => null,
        };
    }

    /**
     * Upserts one daily metric row, keyed by account + campaign + date.
     *
     * @param array<string, mixed> $metric
     */
    private function upsertMetric(AdsAccount $account, array $metric): bool
    {
        $dateStr = (string) ($metric['date'] ?? '');
        if ('' === $dateStr) {
            return false;
        }

        try {
            $metricDate = new \DateTime($dateStr);
        } catch (\Throwable) {
            return false;
        }

        $campaignId = $this->resolveCampaignId($account, (string) ($metric['externalCampaignId'] ?? ''));
        $keywordId  = $this->resolveKeywordId($account, (string) ($metric['externalKeywordId'] ?? ''));

        $existing = $this->entityManager->getRepository(AdsMetricDaily::class)->findOneBy([
            'accountId'  => (int) $account->getId(),
            'campaignId' => $campaignId,
            'keywordId'  => $keywordId,
            'metricDate' => $metricDate,
        ]);

        if (!$existing instanceof AdsMetricDaily) {
            $existing = new AdsMetricDaily();
            $existing->setProvider($account->getProvider())
                ->setAccountId((int) $account->getId())
                ->setCampaignId($campaignId)
                ->setKeywordId($keywordId)
                ->setMetricDate($metricDate)
                ->setCreatedAt(new \DateTime());
        }

        $existing->setImpressions((int) ($metric['impressions'] ?? 0))
            ->setClicks((int) ($metric['clicks'] ?? 0))
            ->setCost((float) ($metric['cost'] ?? 0.0))
            ->setConversions((float) ($metric['conversions'] ?? 0.0))
            ->setConversionValue((float) ($metric['conversionValue'] ?? 0.0))
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($existing);

        return true;
    }

    private function resolveKeywordId(AdsAccount $account, string $externalKeywordId): ?int
    {
        if ('' === $externalKeywordId) {
            return null;
        }

        $cacheKey = $account->getProvider().':'.$externalKeywordId;
        if (array_key_exists($cacheKey, $this->keywordIdCache)) {
            return $this->keywordIdCache[$cacheKey];
        }

        $keyword = $this->entityManager->getRepository(\MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsKeyword::class)->findOneBy([
            'provider'          => $account->getProvider(),
            'externalKeywordId' => $externalKeywordId,
        ]);

        $id = $keyword instanceof \MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsKeyword ? (int) $keyword->getId() : null;
        $this->keywordIdCache[$cacheKey] = $id;

        return $id;
    }

    /**
     * Maps a provider-side campaign ID to the local AdsCampaign row.
     * Returns null when the campaign has not been synced locally yet.
     */
    private function resolveCampaignId(AdsAccount $account, string $externalCampaignId): ?int
    {
        if ('' === $externalCampaignId) {
            return null;
        }

        $cacheKey = $account->getProvider().':'.$externalCampaignId;

        if (array_key_exists($cacheKey, $this->campaignIdCache)) {
            return $this->campaignIdCache[$cacheKey];
        }

        $campaign = $this->entityManager->getRepository(AdsCampaign::class)->findOneBy([
            'provider'           => $account->getProvider(),
            'externalCampaignId' => $externalCampaignId,
        ]);

        $id = $campaign instanceof AdsCampaign ? (int) $campaign->getId() : null;
        $this->campaignIdCache[$cacheKey] = $id;

        return $id;
    }
}
