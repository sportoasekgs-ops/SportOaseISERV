<?php

namespace SportOase\Service;

use SportOase\Entity\Booking;
use SportOase\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    private const PERIODS = [
        1 => ['start' => '07:50', 'end' => '08:35'],
        2 => ['start' => '08:35', 'end' => '09:20'],
        3 => ['start' => '09:40', 'end' => '10:25'],
        4 => ['start' => '10:30', 'end' => '11:15'],
        5 => ['start' => '11:20', 'end' => '12:05'],
        6 => ['start' => '12:10', 'end' => '12:55'],
    ];

    private const MAX_STUDENTS_PER_PERIOD = 5;
    private const BOOKING_ADVANCE_MINUTES = 60;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getWeekData(int $weekOffset = 0): array
    {
        $monday = new \DateTime('monday this week');
        $monday->modify('+' . $weekOffset . ' weeks');
        
        $weekDays = [];
        for ($i = 0; $i < 5; $i++) {
            $date = clone $monday;
            $date->modify('+' . $i . ' days');
            $weekDays[] = [
                'date' => $date,
                'weekday' => $date->format('l'),
                'formatted' => $date->format('d.m.Y'),
            ];
        }
        
        return [
            'days' => $weekDays,
            'periods' => self::PERIODS,
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
        $booking->setStudentsJson(json_decode($data['students_json'], true) ?? []);
        $booking->setOfferType($data['offer_type']);
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
        if (isset($data['students_json'])) {
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
        if ($date->format('N') >= 6) {
            throw new \Exception('Wochenenden können nicht gebucht werden.');
        }
        
        $now = new \DateTime();
        $periodStart = clone $date;
        $periodStartTime = self::PERIODS[$period]['start'];
        $periodStart->setTime(...explode(':', $periodStartTime));
        
        $diffMinutes = ($periodStart->getTimestamp() - $now->getTimestamp()) / 60;
        if ($diffMinutes < self::BOOKING_ADVANCE_MINUTES) {
            throw new \Exception('Buchungen müssen mindestens ' . self::BOOKING_ADVANCE_MINUTES . ' Minuten im Voraus erfolgen.');
        }
        
        $existing = $this->entityManager
            ->getRepository(Booking::class)
            ->findOneBy(['date' => $date, 'period' => $period]);
        
        if ($existing) {
            throw new \Exception('Dieser Zeitslot ist bereits gebucht.');
        }
        
        $students = json_decode($data['students_json'], true) ?? [];
        if (count($students) > self::MAX_STUDENTS_PER_PERIOD) {
            throw new \Exception('Maximale Anzahl von Schülern überschritten (' . self::MAX_STUDENTS_PER_PERIOD . ').');
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
