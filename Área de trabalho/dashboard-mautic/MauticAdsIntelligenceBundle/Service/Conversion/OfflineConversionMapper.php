<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Service\Conversion;

use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsConversionQueue;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsLeadAttribution;

class OfflineConversionMapper
{
    /**
     * Conversion values per event type.
     *
     * @var array<string, float>
     */
    private array $conversionValues = [
        'lead'        => 1.0,
        'hot_lead'    => 5.0,
        'opportunity' => 20.0,
        'customer'    => 0.0, // Use actual revenue when available
    ];

    /**
     * Maps a Mautic event to a conversion queue entry.
     */
    public function mapEvent(
        int $leadId,
        int $accountId,
        string $provider,
        string $eventType,
        \DateTimeInterface $eventTime,
        ?AdsLeadAttribution $attribution = null,
        ?float $revenueOverride = null,
        string $currency = 'BRL',
    ): AdsConversionQueue {
        $queue = new AdsConversionQueue();
        $queue->setLeadId($leadId);
        $queue->setAccountId($accountId);
        $queue->setProvider($provider);
        $queue->setConversionName($this->getConversionName($eventType));
        $queue->setConversionValue($revenueOverride ?? ($this->conversionValues[$eventType] ?? 0.0));
        $queue->setConversionCurrency($currency);
        $queue->setConversionTime($eventTime);

        if ($attribution !== null) {
            $queue->setAttributionId($attribution->getId());
            $queue->setClickId($attribution->getClickId());
            $queue->setClickIdType($attribution->getClickIdType());
        }

        return $queue;
    }

    /**
     * Sets hashed PII data on the conversion queue entry.
     */
    public function applyHashedPii(AdsConversionQueue $queue, ?string $email, ?string $phone): void
    {
        if ($email !== null) {
            $normalized = strtolower(trim($email));
            if ('' !== $normalized) {
                $queue->setHashedEmail(hash('sha256', $normalized));
            }
        }

        if ($phone !== null) {
            $normalized = preg_replace('/[^0-9+]/', '', $phone) ?? $phone;
            if ('' !== $normalized) {
                $queue->setHashedPhone(hash('sha256', $normalized));
            }
        }
    }

    private function getConversionName(string $eventType): string
    {
        return match ($eventType) {
            'lead'        => 'Lead',
            'hot_lead'    => 'Lead Quente',
            'opportunity' => 'Oportunidade',
            'customer'    => 'Cliente',
            default       => ucfirst(str_replace('_', ' ', $eventType)),
        };
    }

    /**
     * @param array<string, float> $values
     */
    public function setConversionValues(array $values): void
    {
        $this->conversionValues = array_merge($this->conversionValues, $values);
    }
}
