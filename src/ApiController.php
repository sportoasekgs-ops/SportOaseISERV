<?php

namespace App;

class ApiController
{
    private BookingService $bookingService;
    private IServAuthenticator $authenticator;
    private array $user;

    public function __construct(BookingService $bookingService, IServAuthenticator $authenticator)
    {
        $this->bookingService = $bookingService;
        $this->authenticator = $authenticator;
    }

    private function requireAuth(): void
    {
        if (!$this->authenticator->validateRequest('', $this->user)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    private function requireAdmin(): void
    {
        $this->requireAuth();
        if ($this->user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
    }

    private function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function getSchedule(): void
    {
        $this->requireAuth();

        try {
            $schedule = $this->bookingService->getWeeklySchedule();
            $this->json($schedule);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function listBookings(): void
    {
        $this->requireAuth();

        try {
            $supabase = $GLOBALS['supabase'];
            $bookings = $supabase
                ->from('sportoase_bookings')
                ->select('*')
                ->eq('teacher_id', $this->user['id'])
                ->order('date', 'desc')
                ->execute()
                ->data ?? [];

            $this->json(['bookings' => $bookings]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function createBooking(): void
    {
        $this->requireAuth();

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $booking = $this->bookingService->createBooking(
                $this->user['id'],
                $data['date'],
                $data['period'],
                $data['teacher_name'],
                $data['teacher_class'],
                $data['students'],
                $data['offer_label']
            );

            $this->json(['booking' => $booking], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteBooking(): void
    {
        $this->requireAuth();

        try {
            $bookingId = $_GET['id'] ?? null;
            if (!$bookingId) {
                throw new \Exception('Booking ID required');
            }

            $this->bookingService->deleteBooking($bookingId, $this->user['id']);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function blockSlot(): void
    {
        $this->requireAdmin();

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $supabase = $GLOBALS['supabase'];

            $blockDate = new \DateTime($data['date'], new \DateTimeZone('Europe/Berlin'));
            $weekday = $blockDate->format('a');

            $supabase
                ->from('sportoase_blocked_slots')
                ->insert([
                    'date' => $data['date'],
                    'period' => $data['period'],
                    'weekday' => $weekday,
                    'reason' => $data['reason'] ?? 'Beratung',
                    'blocked_by_id' => $this->user['id'],
                    'created_at' => date('c'),
                ])
                ->execute();

            $this->json(['success' => true], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function unblockSlot(): void
    {
        $this->requireAdmin();

        try {
            $date = $_GET['date'] ?? null;
            $period = $_GET['period'] ?? null;

            if (!$date || !$period) {
                throw new \Exception('Date and period required');
            }

            $supabase = $GLOBALS['supabase'];
            $supabase
                ->from('sportoase_blocked_slots')
                ->delete()
                ->eq('date', $date)
                ->eq('period', $period)
                ->execute();

            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
