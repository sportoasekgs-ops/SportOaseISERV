<?php

namespace App;

use Supabase\Client as SupabaseClient;

class BookingService
{
    private SupabaseClient $supabase;
    private array $config;

    public function __construct(SupabaseClient $supabase, array $config = [])
    {
        $this->supabase = $supabase;
        $this->config = array_merge([
            'max_students_per_period' => 5,
            'booking_advance_minutes' => 60,
            'period_times' => [
                1 => ['start' => '07:50', 'end' => '08:35'],
                2 => ['start' => '08:35', 'end' => '09:20'],
                3 => ['start' => '09:40', 'end' => '10:25'],
                4 => ['start' => '10:25', 'end' => '11:20'],
                5 => ['start' => '11:40', 'end' => '12:25'],
                6 => ['start' => '12:25', 'end' => '13:10'],
            ],
            'fixed_offers' => [
                'Mon' => [1 => 'Sport I', 3 => 'Sport II', 5 => 'Sport III'],
                'Tue' => [],
                'Wed' => [1 => 'Sport I', 3 => 'Sport II', 5 => 'Sport III'],
                'Thu' => [2 => 'Sport I', 5 => 'Sport II'],
                'Fri' => [2 => 'Sport I', 4 => 'Sport II', 5 => 'Sport III'],
            ],
        ], $config);
    }

    public function getWeeklySchedule(): array
    {
        $today = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $dayOfWeek = $today->format('N');
        $monday = (clone $today)->modify("-" . ($dayOfWeek - 1) . " days");
        $friday = (clone $monday)->modify("+4 days");

        $bookings = $this->supabase
            ->from('sportoase_bookings')
            ->select('*')
            ->gte('date', $monday->format('Y-m-d'))
            ->lte('date', $friday->format('Y-m-d'))
            ->order('date', 'asc')
            ->order('period', 'asc')
            ->execute()
            ->data ?? [];

        $blockedSlots = $this->supabase
            ->from('sportoase_blocked_slots')
            ->select('*')
            ->gte('date', $monday->format('Y-m-d'))
            ->lte('date', $friday->format('Y-m-d'))
            ->execute()
            ->data ?? [];

        return [
            'week_start' => $monday->format('Y-m-d'),
            'week_end' => $friday->format('Y-m-d'),
            'bookings' => $bookings,
            'blocked_slots' => $blockedSlots,
            'periods' => $this->config['period_times'],
        ];
    }

    public function createBooking(
        string $teacherId,
        string $date,
        int $period,
        string $teacherName,
        string $teacherClass,
        array $students,
        string $offerLabel
    ): ?array {
        $this->validateBookingRequest($date, $period, $teacherName, $teacherClass, $students);

        $bookingDate = new \DateTime($date, new \DateTimeZone('Europe/Berlin'));
        $weekday = $bookingDate->format('a');

        $studentCount = count($students);
        $currentCount = $this->countStudentsForPeriod($date, $period);

        if (($currentCount + $studentCount) > $this->config['max_students_per_period']) {
            throw new \Exception('Insufficient capacity for this time slot');
        }

        $offerType = $this->determineOfferType($weekday, $period);

        try {
            $result = $this->supabase
                ->from('sportoase_bookings')
                ->insert([
                    'date' => $date,
                    'period' => $period,
                    'weekday' => $weekday,
                    'teacher_id' => $teacherId,
                    'teacher_name' => $teacherName,
                    'teacher_class' => $teacherClass,
                    'students_json' => json_encode($students),
                    'offer_type' => $offerType,
                    'offer_label' => $offerLabel,
                    'created_at' => date('c'),
                ])
                ->execute();

            $booking = $result->data[0] ?? null;

            if ($booking) {
                $this->createNotification($booking['id'], $teacherName, $offerLabel, count($students), $date, $period);
            }

            return $booking;
        } catch (\Exception $e) {
            error_log("Error creating booking: {$e->getMessage()}");
            throw $e;
        }
    }

    public function deleteBooking(string $bookingId, string $teacherId): bool
    {
        try {
            $booking = $this->supabase
                ->from('sportoase_bookings')
                ->select('*')
                ->eq('id', $bookingId)
                ->maybeSingle()
                ->execute();

            if (!$booking->data || $booking->data['teacher_id'] !== $teacherId) {
                throw new \Exception('Booking not found or access denied');
            }

            $this->supabase
                ->from('sportoase_bookings')
                ->delete()
                ->eq('id', $bookingId)
                ->execute();

            return true;
        } catch (\Exception $e) {
            error_log("Error deleting booking: {$e->getMessage()}");
            throw $e;
        }
    }

    public function countStudentsForPeriod(string $date, int $period): int
    {
        $bookings = $this->supabase
            ->from('sportoase_bookings')
            ->select('students_json')
            ->eq('date', $date)
            ->eq('period', $period)
            ->execute()
            ->data ?? [];

        $total = 0;
        foreach ($bookings as $booking) {
            $students = json_decode($booking['students_json'], true) ?? [];
            $total += count($students);
        }

        return $total;
    }

    public function validateBookingRequest(
        string $date,
        int $period,
        string $teacherName,
        string $teacherClass,
        array $students
    ): void {
        if (!$teacherName || !$teacherClass) {
            throw new \InvalidArgumentException('Teacher name and class required');
        }

        if (empty($students) || count($students) > 5) {
            throw new \InvalidArgumentException('Must provide 1-5 students');
        }

        $bookingDate = new \DateTime($date, new \DateTimeZone('Europe/Berlin'));
        $now = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));

        if ($bookingDate < $now) {
            throw new \Exception('Cannot book past dates');
        }

        if ($bookingDate->format('w') == 0 || $bookingDate->format('w') == 6) {
            throw new \Exception('Bookings not available on weekends');
        }

        if ($period < 1 || $period > 6) {
            throw new \InvalidArgumentException('Invalid period');
        }
    }

    private function determineOfferType(string $weekday, int $period): string
    {
        $fixedOffers = $this->config['fixed_offers'][$weekday] ?? [];
        return isset($fixedOffers[$period]) ? 'fest' : 'frei';
    }

    private function createNotification(
        string $bookingId,
        string $teacherName,
        string $offerLabel,
        int $studentCount,
        string $date,
        int $period
    ): void {
        try {
            $message = "New booking: $teacherName registered $studentCount students for $offerLabel on $date (Period $period)";

            $this->supabase
                ->from('sportoase_notifications')
                ->insert([
                    'booking_id' => $bookingId,
                    'recipient_role' => 'admin',
                    'notification_type' => 'new_booking',
                    'message' => $message,
                    'metadata_json' => json_encode([
                        'teacher_name' => $teacherName,
                        'offer_label' => $offerLabel,
                        'students_count' => $studentCount,
                        'date' => $date,
                        'period' => $period,
                    ]),
                    'is_read' => false,
                    'created_at' => date('c'),
                ])
                ->execute();
        } catch (\Exception $e) {
            error_log("Error creating notification: {$e->getMessage()}");
        }
    }
}
