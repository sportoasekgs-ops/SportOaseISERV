<?php

namespace SportOase\Service;

use SportOase\Entity\SystemConfig;
use SportOase\Entity\FixedOfferName;
use SportOase\Entity\FixedOfferPlacement;
use Doctrine\ORM\EntityManagerInterface;

class ConfigService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function get(string $key): array
    {
        $config = $this->entityManager
            ->getRepository(SystemConfig::class)
            ->findOneBy(['configKey' => $key]);

        return $config ? $config->getConfigValue() : [];
    }

    public function set(string $key, array $value): void
    {
        $config = $this->entityManager
            ->getRepository(SystemConfig::class)
            ->findOneBy(['configKey' => $key]);

        if (!$config) {
            $config = new SystemConfig();
            $config->setConfigKey($key);
            $this->entityManager->persist($config);
        }

        $config->setConfigValue($value);
        $this->entityManager->flush();
    }

    public function getPeriods(): array
    {
        return [
            1 => ['start' => '07:50', 'end' => '08:35', 'label' => '07:50 - 08:35'],
            2 => ['start' => '08:35', 'end' => '09:20', 'label' => '08:35 - 09:20'],
            3 => ['start' => '09:40', 'end' => '10:25', 'label' => '09:40 - 10:25'],
            4 => ['start' => '10:25', 'end' => '11:20', 'label' => '10:25 - 11:20'],
            5 => ['start' => '11:40', 'end' => '12:25', 'label' => '11:40 - 12:25'],
            6 => ['start' => '12:25', 'end' => '13:10', 'label' => '12:25 - 13:10'],
        ];
    }

    public function getFreeModules(): array
    {
        return [
            'Aktivierung',
            'Regulation / Entspannung',
            'Konflikt-Reset',
            'Turnen / flexibel',
            'Wochenstart Warm-Up'
        ];
    }

    public function getFixedOfferPlacements(): array
    {
        static $placements = null;
        
        if ($placements === null) {
            $placementEntities = $this->entityManager
                ->getRepository(FixedOfferPlacement::class)
                ->findAll();
            
            $placements = [];
            foreach ($placementEntities as $placement) {
                $placements[$placement->getWeekday()][$placement->getPeriod()] = $placement->getOfferName();
            }
        }
        
        return $placements;
    }

    public function getOfferCustomName(string $offerKey): string
    {
        static $customNames = null;
        
        if ($customNames === null) {
            $offerNames = $this->entityManager
                ->getRepository(FixedOfferName::class)
                ->findAll();
            
            $customNames = [];
            foreach ($offerNames as $offerName) {
                $customNames[$offerName->getOfferKey()] = $offerName->getCustomName();
            }
        }
        
        return $customNames[$offerKey] ?? $offerKey;
    }

    public function getFixedOfferKey(\DateTime $date, int $period): ?string
    {
        $dayOfWeek = (int)$date->format('N');
        $fixedOffers = $this->getFixedOfferPlacements();
        return $fixedOffers[$dayOfWeek][$period] ?? null;
    }

    public function getFixedOfferDisplayName(\DateTime $date, int $period): ?string
    {
        $offerKey = $this->getFixedOfferKey($date, $period);
        
        if ($offerKey === null) {
            return null;
        }
        
        return $this->getOfferCustomName($offerKey);
    }

    public function hasFixedOffer(\DateTime $date, int $period): bool
    {
        return $this->getFixedOfferKey($date, $period) !== null;
    }

    public function isSlotBookable(\DateTime $date, int $period): bool
    {
        $dayOfWeek = (int)$date->format('N');
        if ($dayOfWeek >= 6) {
            return false;
        }
        
        $periods = $this->getPeriods();
        if (!isset($periods[$period])) {
            return false;
        }
        
        $slotDateTime = clone $date;
        $timeStart = $periods[$period]['start'];
        [$hour, $minute] = explode(':', $timeStart);
        $slotDateTime->setTime((int)$hour, (int)$minute);
        
        $now = new \DateTime();
        $diffMinutes = ($slotDateTime->getTimestamp() - $now->getTimestamp()) / 60;
        
        return $diffMinutes >= $this->getBookingAdvanceMinutes();
    }

    public function getSystemSettings(): array
    {
        return [
            'max_students_per_period' => 5,
            'booking_advance_minutes' => 60,
            'contact_email' => 'morelli.maurizio@kgs-pattensen.de',
            'contact_phone' => '0151 40349764'
        ];
    }

    public function getMaxStudentsPerPeriod(): int
    {
        return $this->getSystemSettings()['max_students_per_period'];
    }

    public function getBookingAdvanceMinutes(): int
    {
        return $this->getSystemSettings()['booking_advance_minutes'];
    }

    public function getContactEmail(): string
    {
        return $this->getSystemSettings()['contact_email'];
    }

    public function getContactPhone(): string
    {
        return $this->getSystemSettings()['contact_phone'];
    }
}
