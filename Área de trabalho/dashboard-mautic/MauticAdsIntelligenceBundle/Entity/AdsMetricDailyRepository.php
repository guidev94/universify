<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AdsMetricDaily>
 */
class AdsMetricDailyRepository extends CommonRepository
{
    /**
     * @return AdsMetricDaily[]
     */
    public function findByDateRange(string $provider, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.provider = :provider')
            ->andWhere('m.metricDate >= :from')
            ->andWhere('m.metricDate <= :to')
            ->setParameter('provider', $provider)
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->orderBy('m.metricDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AdsMetricDaily[]
     */
    public function findByCampaignAndDate(int $campaignId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.campaignId = :campaignId')
            ->andWhere('m.metricDate >= :from')
            ->andWhere('m.metricDate <= :to')
            ->setParameter('campaignId', $campaignId)
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->orderBy('m.metricDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
