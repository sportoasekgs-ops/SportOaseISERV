<?php

namespace SportOase\Controller;

use SportOase\Entity\Booking;
use SportOase\Service\BookingService;
use SportOase\Service\ConfigService;
use SportOase\Service\EmailService;
use SportOase\Service\AuditService;
use SportOase\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sportoase/booking')]
class BookingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingService $bookingService,
        private ConfigService $configService,
        private EmailService $emailService,
        private AuditService $auditService,
        private GoogleCalendarService $googleCalendarService
    ) {
    }

    #[Route('/create', name: 'sportoase_booking_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            try {
                $requestData = $request->request->all();
                
                $offerParam = $request->query->get('offer');
                $fixedOfferData = null;
                if ($offerParam && !empty($requestData['date']) && !empty($requestData['period'])) {
                    $dateObj = new \DateTime($requestData['date']);
                    $weekday = (int)$dateObj->format('N');
                    $stableOfferId = $weekday . '_' . $requestData['period'];
                    
                    if ($stableOfferId === $offerParam && $this->configService->hasFixedOffer($dateObj, (int)$requestData['period'])) {
                        $fixedOfferData = [
                            'offer_type' => 'fixed',
                            'offer_label' => $this->configService->getFixedOfferDisplayName($dateObj, (int)$requestData['period']),
                        ];
                    }
                }
                
                $normalizedData = $this->normalizeBookingData($requestData, $fixedOfferData);
                
                $booking = $this->bookingService->createBooking(
                    $user,
                    $normalizedData
                );
                
                $calendarEventId = $this->googleCalendarService->createEvent($booking);
                if ($calendarEventId) {
                    $booking->setCalendarEventId($calendarEventId);
                    $this->entityManager->flush();
                }
                
                $this->auditService->log(
                    'Booking',
                    $booking->getId(),
                    'create',
                    $user,
                    [],
                    'Buchung erstellt: ' . $booking->getOfferLabel() . ' für ' . $booking->getDate()->format('d.m.Y')
                );
                
                $this->emailService->sendBookingNotification($booking);
                
                $this->addFlash('success', 'Buchung erfolgreich erstellt!');
                return $this->redirectToRoute('sportoase_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }
        
        $date = $request->query->get('date');
        $period = $request->query->get('period');
        $offerParam = $request->query->get('offer');
        
        $suggestedOfferType = 'free';
        $suggestedOfferLabel = '';
        
        if ($offerParam && $date && $period) {
            $dateObj = new \DateTime($date);
            $weekday = (int)$dateObj->format('N');
            $stableOfferId = $weekday . '_' . $period;
            
            if ($stableOfferId === $offerParam && $this->configService->hasFixedOffer($dateObj, (int)$period)) {
                $suggestedOfferType = 'fixed';
                $suggestedOfferLabel = $this->configService->getFixedOfferDisplayName($dateObj, (int)$period);
            }
        }
        
        return $this->render('@SportOase/booking/create.html.twig', [
            'date' => $date,
            'period' => $period,
            'suggested_offer_type' => $suggestedOfferType,
            'suggested_offer_label' => $suggestedOfferLabel,
        ]);
    }

    #[Route('/{id}/delete', name: 'sportoase_booking_delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking): Response
    {
        $user = $this->getUser();
        
        if ($booking->getTeacher() !== $user && !$user->isAdmin()) {
            throw $this->createAccessDeniedException('Sie haben keine Berechtigung, diese Buchung zu löschen.');
        }
        
        if ($this->isCsrfTokenValid('delete-booking-' . $booking->getId(), $request->request->get('_token'))) {
            $bookingData = [
                'date' => $booking->getDate()->format('d.m.Y'),
                'period' => $booking->getPeriod(),
                'offerLabel' => $booking->getOfferLabel()
            ];
            
            if ($booking->getCalendarEventId()) {
                $this->googleCalendarService->deleteEvent($booking->getCalendarEventId());
            }
            
            $this->auditService->log(
                'Booking',
                $booking->getId(),
                'delete',
                $user,
                $bookingData,
                'Buchung gelöscht: ' . $booking->getOfferLabel() . ' für ' . $booking->getDate()->format('d.m.Y')
            );
            
            $this->entityManager->remove($booking);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Buchung erfolgreich gelöscht!');
        }
        
        return $this->redirectToRoute('sportoase_dashboard');
    }

    private function normalizeBookingData(array $data, ?array $fixedOfferDefaults = null): array
    {
        $students = [];
        if (!empty($data['students_json'])) {
            $decoded = json_decode($data['students_json'], true);
            $students = is_array($decoded) ? $decoded : [];
        }
        
        $offerType = !empty($data['offer_type']) ? $data['offer_type'] : ($fixedOfferDefaults['offer_type'] ?? 'free');
        $offerLabel = !empty($data['offer_label']) ? $data['offer_label'] : ($fixedOfferDefaults['offer_label'] ?? '');
        
        $normalized = [
            'date' => $data['date'] ?? null,
            'period' => $data['period'] ?? null,
            'teacher_name' => $data['teacher_name'] ?? '',
            'teacher_class' => $data['teacher_class'] ?? '',
            'students_json' => json_encode($students),
            'students' => $students,
            'offer_type' => $offerType,
            'offer_label' => $offerLabel,
        ];
        
        return $normalized;
    }
}
