<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsConversionQueue;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsLeadAttribution;

class OfflineConversionStageEnqueueService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OfflineConversionQueueService $queueService,
        private readonly OfflineConversionSettingsService $settingsService,
    ) {
    }

    /**
     * @return array{matched: int, queued: int, skipped: int}
     */
    public function enqueueConvertedLeads(): array
    {
        $settings = $this->settingsService->getSettings();
        $tagIds = $settings['tag_ids'];

        if ([] === $tagIds) {
            return ['matched' => 0, 'queued' => 0, 'skipped' => 0];
        }

        $rows = $this->em->getConnection()->createQueryBuilder()
            ->select('a.id attribution_id, a.lead_id, a.provider, a.account_id, a.click_id, a.click_id_type, l.email, l.mobile, l.phone')
            ->from(MAUTIC_TABLE_PREFIX.'ads_lead_attribution', 'a')
            ->innerJoin('a', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = a.lead_id')
            ->innerJoin('l', MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'ltx', 'ltx.lead_id = l.id')
            ->where('ltx.tag_id IN (:tagIds)')
            ->andWhere('a.provider IN (:providers)')
            ->groupBy('a.id, a.lead_id, a.provider, a.account_id, a.click_id, a.click_id_type, l.email, l.mobile, l.phone')
            ->setParameter('tagIds', $tagIds, ArrayParameterType::INTEGER)
            ->setParameter('providers', ['google', 'microsoft'], ArrayParameterType::STRING)
            ->executeQuery()
            ->fetchAllAssociative();

        $queued = 0;
        $skipped = 0;
        $processed = [];

        foreach ($rows as $row) {
            $provider = (string) $row['provider'];
            $attribution = $this->em->getRepository(AdsLeadAttribution::class)->find((int) $row['attribution_id']);
            if (!$attribution instanceof AdsLeadAttribution) {
                ++$skipped;
                continue;
            }

            $accountId = $this->getAccountIdForAttribution($attribution, $settings['google_conversion_action']);
            if (null === $accountId) {
                ++$skipped;
                continue;
            }

            $key = $this->getQueueKey((int) $row['lead_id'], $accountId, $provider);
            $processed[$key] = true;

            if ($this->alreadyQueued((int) $row['lead_id'], $accountId, $provider, $settings['conversion_name'])) {
                ++$skipped;
                continue;
            }

            $queue = $this->queueService->enqueue(
                (int) $row['lead_id'],
                $accountId,
                $provider,
                $settings['conversion_name'],
                new \DateTime(),
                $attribution,
                $settings['conversion_value'],
                isset($row['email']) ? (string) $row['email'] : null,
                isset($row['mobile']) && '' !== (string) $row['mobile'] ? (string) $row['mobile'] : (isset($row['phone']) ? (string) $row['phone'] : null),
                $settings['currency'],
            );
            $queue->setConversionName($settings['conversion_name']);
            $queue->setPayload([
                'google_conversion_action' => $settings['google_conversion_action'],
            ]);
            $this->em->flush();

            ++$queued;
        }

        $googleAccount = $this->getGoogleAccountFromConversionAction($settings['google_conversion_action']);
        if ($googleAccount instanceof AdsAccount && null !== $googleAccount->getId()) {
            $taggedLeads = $this->em->getConnection()->createQueryBuilder()
                ->select('DISTINCT l.id lead_id, l.email, l.mobile, l.phone')
                ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                ->innerJoin('l', MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'ltx', 'ltx.lead_id = l.id')
                ->where('ltx.tag_id IN (:tagIds)')
                ->setParameter('tagIds', $tagIds, ArrayParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($taggedLeads as $row) {
                $key = $this->getQueueKey((int) $row['lead_id'], (int) $googleAccount->getId(), 'google');
                if (isset($processed[$key])) {
                    continue;
                }

                $processed[$key] = true;

                if ($this->alreadyQueued((int) $row['lead_id'], (int) $googleAccount->getId(), 'google', $settings['conversion_name'])) {
                    ++$skipped;
                    continue;
                }

                $email = isset($row['email']) && '' !== (string) $row['email'] ? (string) $row['email'] : null;
                $phone = isset($row['mobile']) && '' !== (string) $row['mobile'] ? (string) $row['mobile'] : (isset($row['phone']) && '' !== (string) $row['phone'] ? (string) $row['phone'] : null);
                if (null === $email && null === $phone) {
                    ++$skipped;
                    continue;
                }

                $queue = $this->queueService->enqueue(
                    (int) $row['lead_id'],
                    (int) $googleAccount->getId(),
                    'google',
                    $settings['conversion_name'],
                    new \DateTime(),
                    null,
                    $settings['conversion_value'],
                    $email,
                    $phone,
                    $settings['currency'],
                );
                $queue->setConversionName($settings['conversion_name']);
                $queue->setPayload([
                    'google_conversion_action' => $settings['google_conversion_action'],
                ]);
                $this->em->flush();

                ++$queued;
            }

            $rows = array_merge($rows, $taggedLeads);
        }

        $microsoftAccount = $this->getSingleActiveAccount('microsoft');
        if ($microsoftAccount instanceof AdsAccount && null !== $microsoftAccount->getId()) {
            $taggedLeads = $this->em->getConnection()->createQueryBuilder()
                ->select('DISTINCT l.id lead_id, l.email, l.mobile, l.phone')
                ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                ->innerJoin('l', MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'ltx', 'ltx.lead_id = l.id')
                ->where('ltx.tag_id IN (:tagIds)')
                ->setParameter('tagIds', $tagIds, ArrayParameterType::INTEGER)
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($taggedLeads as $row) {
                $key = $this->getQueueKey((int) $row['lead_id'], (int) $microsoftAccount->getId(), 'microsoft');
                if (isset($processed[$key])) {
                    continue;
                }

                $processed[$key] = true;

                if ($this->alreadyQueued((int) $row['lead_id'], (int) $microsoftAccount->getId(), 'microsoft', $settings['conversion_name'])) {
                    ++$skipped;
                    continue;
                }

                $email = isset($row['email']) && '' !== (string) $row['email'] ? (string) $row['email'] : null;
                $phone = isset($row['mobile']) && '' !== (string) $row['mobile'] ? (string) $row['mobile'] : (isset($row['phone']) && '' !== (string) $row['phone'] ? (string) $row['phone'] : null);
                if (null === $email && null === $phone) {
                    ++$skipped;
                    continue;
                }

                $queue = $this->queueService->enqueue(
                    (int) $row['lead_id'],
                    (int) $microsoftAccount->getId(),
                    'microsoft',
                    $settings['conversion_name'],
                    new \DateTime(),
                    null,
                    $settings['conversion_value'],
                    $email,
                    $phone,
                    $settings['currency'],
                );
                $queue->setConversionName($settings['conversion_name']);
                $this->em->flush();

                ++$queued;
            }

            $rows = array_merge($rows, $taggedLeads);
        }

        return ['matched' => count($rows), 'queued' => $queued, 'skipped' => $skipped];
    }

    private function alreadyQueued(int $leadId, int $accountId, string $provider, string $conversionName): bool
    {
        return null !== $this->em->getRepository(AdsConversionQueue::class)->findOneBy([
            'leadId'         => $leadId,
            'accountId'      => $accountId,
            'provider'       => $provider,
            'conversionName' => $conversionName,
        ]);
    }

    private function getGoogleAccountFromConversionAction(string $conversionAction): ?AdsAccount
    {
        if (!preg_match('#^customers/(\d+)/conversionActions/\d+$#', $conversionAction, $matches)) {
            return null;
        }

        $externalAccountId = $matches[1];

        return $this->em->getRepository(AdsAccount::class)->findOneBy([
            'provider'          => 'google',
            'externalAccountId' => $externalAccountId,
        ]);
    }

    private function getAccountIdForAttribution(AdsLeadAttribution $attribution, string $googleConversionAction): ?int
    {
        if (null !== $attribution->getAccountId()) {
            return $attribution->getAccountId();
        }

        if ('google' === $attribution->getProvider()) {
            $account = $this->getGoogleAccountFromConversionAction($googleConversionAction);

            return $account instanceof AdsAccount ? $account->getId() : null;
        }

        if ('microsoft' === $attribution->getProvider()) {
            $account = $this->getSingleActiveAccount('microsoft');

            return $account instanceof AdsAccount ? $account->getId() : null;
        }

        return null;
    }

    private function getSingleActiveAccount(string $provider): ?AdsAccount
    {
        $accounts = $this->em->getRepository(AdsAccount::class)->findBy([
            'provider' => $provider,
            'status'   => 'active',
        ]);

        return 1 === count($accounts) ? $accounts[0] : null;
    }

    private function getQueueKey(int $leadId, int $accountId, string $provider): string
    {
        return $leadId.':'.$accountId.':'.$provider;
    }
}
