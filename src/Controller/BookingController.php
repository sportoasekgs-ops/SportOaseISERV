<?php

namespace SportOase\Controller;

use SportOase\Entity\Booking;
use SportOase\Service\BookingService;
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
                $booking = $this->bookingService->createBooking(
                    $user,
                    $request->request->all()
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
        
        return $this->render('@SportOase/booking/create.html.twig', [
            'date' => $date,
            'period' => $period,
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
}
