<?php

namespace SportOase\Controller;

use SportOase\Entity\Booking;
use SportOase\Entity\BlockedSlot;
use SportOase\Entity\SlotName;
use SportOase\Service\BookingService;
use SportOase\Service\ConfigService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sportoase')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingService $bookingService,
        private ConfigService $configService
    ) {
    }

    #[Route('/', name: 'sportoase_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $user = $this->getUser();
        
        $weekOffset = (int) $request->query->get('week', 0);
        $weekData = $this->bookingService->getWeekData($weekOffset);
        
        $userBookings = $this->entityManager
            ->getRepository(Booking::class)
            ->findBy(['teacher' => $user], ['date' => 'DESC']);
        
        $slotNames = $this->entityManager
            ->getRepository(SlotName::class)
            ->findAll();
        
        $blockedSlots = $this->entityManager
            ->getRepository(BlockedSlot::class)
            ->findAll();
        
        $fixedOfferPlacements = $this->configService->getFixedOfferPlacements();
        
        $startDate = $weekData['start_date'];
        $endDate = (clone $startDate)->modify('+6 days');
        
        $allBookings = $this->entityManager
            ->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->where('b.date >= :start_date')
            ->andWhere('b.date <= :end_date')
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate)
            ->getQuery()
            ->getResult();
        
        $slotMetadata = [];
        $availableSlots = 0;
        
        foreach ($weekData['days'] as $day) {
            $dateObj = $day['date'];
            $weekday = (int)$dateObj->format('N');
            
            foreach ($this->configService->getPeriods() as $period => $times) {
                $key = $dateObj->format('Y-m-d') . '_' . $period;
                $isBookable = $this->configService->isSlotBookable($dateObj, $period);
                
                $isBlocked = false;
                foreach ($blockedSlots as $blocked) {
                    if ($blocked->getDate()->format('Y-m-d') === $dateObj->format('Y-m-d') && $blocked->getPeriod() === $period) {
                        $isBlocked = true;
                        break;
                    }
                }
                
                $booking = null;
                foreach ($allBookings as $b) {
                    if ($b->getDate()->format('Y-m-d') === $dateObj->format('Y-m-d') && $b->getPeriod() === $period) {
                        $booking = $b;
                        break;
                    }
                }
                
                $hasFixedOffer = $this->configService->hasFixedOffer($dateObj, $period);
                $stableOfferId = $hasFixedOffer ? $weekday . '_' . $period : null;
                
                $slotMetadata[$key] = [
                    'is_bookable' => $isBookable && !$isBlocked && !$booking,
                    'is_booked' => $booking !== null,
                    'booking_owner' => $booking ? $booking->getTeacherName() : null,
                    'booking_label' => $booking ? $booking->getOfferLabel() : null,
                    'fixed_offer_id' => $stableOfferId,
                    'fixed_offer_display' => $this->configService->getFixedOfferDisplayName($dateObj, $period),
                ];
                
                if ($isBookable && !$isBlocked && !$booking) {
                    $availableSlots++;
                }
            }
        }
        
        return $this->render('@SportOase/dashboard.html.twig', [
            'user' => $user,
            'week_data' => $weekData,
            'user_bookings' => $userBookings,
            'slot_names' => $slotNames,
            'blocked_slots' => $blockedSlots,
            'periods' => $this->configService->getPeriods(),
            'free_modules' => $this->configService->getFreeModules(),
            'fixed_offer_placements' => $fixedOfferPlacements,
            'slot_metadata' => $slotMetadata,
            'available_slots' => $availableSlots,
            'contact_email' => $this->configService->getContactEmail(),
            'contact_phone' => $this->configService->getContactPhone(),
            'week_offset' => $weekOffset,
        ]);
    }
}
