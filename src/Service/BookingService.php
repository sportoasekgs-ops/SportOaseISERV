<?php

namespace SportOase\Service;

use SportOase\Entity\Booking;
use SportOase\Entity\User;
use SportOase\Entity\BlockedSlot;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ConfigService $configService
    ) {
    }

    public function getWeekData(int $weekOffset = 0): array
    {
        $now = new \DateTime();
        $dayOfWeek = (int)$now->format('N');
        
        if ($weekOffset === 0 && $dayOfWeek >= 5) {
            $monday = new \DateTime('next monday');
        } else {
            $monday = new \DateTime('monday this week');
            if ($weekOffset > 0) {
                $monday->modify("+{$weekOffset} week");
            } elseif ($weekOffset < 0) {
                $monday->modify("{$weekOffset} week");
            }
        }
        
        $weekDays = [];
        for ($i = 0; $i < 5; $i++) {
            $date = clone $monday;
            $date->modify("+{$i} days");
            $weekDays[] = [
                'date' => $date,
                'weekday' => $date->format('l'),
                'formatted' => $date->format('d.m.Y'),
            ];
        }
        
        return [
            'days' => $weekDays,
            'periods' => $this->configService->getPeriods(),
            'start_date' => $monday,
        ];
    }

    public function createBooking(User $user, array $data): Booking
    {
        $date = new \DateTime($data['date']);
        $period = (int) $data['period'];
        
        $this->validateBooking($date, $period, $data);
        
        $booking = new Booking();
        $booking->setDate($date);
        $booking->setPeriod($period);
        $booking->setWeekday($date->format('l'));
        $booking->setTeacher($user);
        $booking->setTeacherName($data['teacher_name']);
        $booking->setTeacherClass($data['teacher_class']);
        
        $students = $data['students'] ?? [];
        if (!is_array($students)) {
            $students = json_decode($data['students_json'] ?? '[]', true) ?? [];
        }
        $booking->setStudentsJson($students);
        
        $booking->setOfferType($data['offer_type'] ?? 'free');
        $booking->setOfferLabel($data['offer_label']);
        
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        
        return $booking;
    }

    public function updateBooking(Booking $booking, array $data): Booking
    {
        if (isset($data['teacher_name'])) {
            $booking->setTeacherName($data['teacher_name']);
        }
        if (isset($data['teacher_class'])) {
            $booking->setTeacherClass($data['teacher_class']);
        }
        if (isset($data['students'])) {
            $students = is_array($data['students']) ? $data['students'] : json_decode($data['students_json'] ?? '[]', true) ?? [];
            $booking->setStudentsJson($students);
        } elseif (isset($data['students_json'])) {
            $booking->setStudentsJson(json_decode($data['students_json'], true) ?? []);
        }
        if (isset($data['offer_label'])) {
            $booking->setOfferLabel($data['offer_label']);
        }
        
        $booking->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
        
        return $booking;
    }

    private function validateBooking(\DateTime $date, int $period, array $data): void
    {
        if (!$this->configService->isSlotBookable($date, $period)) {
            throw new \Exception('Dieser Slot ist nicht buchbar (Wochenende oder zu kurzer Zeitvorlauf).');
        }
        
        $blocked = $this->entityManager
            ->getRepository(BlockedSlot::class)
            ->findOneBy(['date' => $date, 'period' => $period]);
        if ($blocked) {
            throw new \Exception('Dieser Slot ist gesperrt: ' . $blocked->getReason());
        }
        
        $existing = $this->entityManager
            ->getRepository(Booking::class)
            ->findOneBy(['date' => $date, 'period' => $period]);
        
        if ($existing) {
            throw new \Exception('Dieser Zeitslot ist bereits gebucht.');
        }
        
        $students = $data['students'] ?? [];
        if (!is_array($students)) {
            $students = json_decode($data['students_json'] ?? '[]', true) ?? [];
        }
        
        if (count($students) > $this->configService->getMaxStudentsPerPeriod()) {
            throw new \Exception('Maximale Anzahl von Schülern überschritten (' . $this->configService->getMaxStudentsPerPeriod() . ').');
        }
        
        $studentNames = array_column($students, 'name');
        $this->checkDoubleBooking($studentNames, $date, $period);
    }

    private function checkDoubleBooking(array $studentNames, \DateTime $date, int $period): void
    {
        $bookings = $this->entityManager
            ->getRepository(Booking::class)
            ->findBy(['date' => $date, 'period' => $period]);
        
        foreach ($bookings as $booking) {
            $bookedStudents = array_column($booking->getStudentsJson(), 'name');
            $duplicates = array_intersect($studentNames, $bookedStudents);
            
            if (!empty($duplicates)) {
                throw new \Exception('Schüler bereits gebucht: ' . implode(', ', $duplicates));
            }
        }
    }
}
