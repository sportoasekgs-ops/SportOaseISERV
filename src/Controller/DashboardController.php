<?php

namespace SportOase\Controller;

use SportOase\Entity\Booking;
use SportOase\Entity\BlockedSlot;
use SportOase\Entity\SlotName;
use SportOase\Service\BookingService;
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
        private BookingService $bookingService
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
        
        return $this->render('@SportOase/dashboard.html.twig', [
            'user' => $user,
            'week_data' => $weekData,
            'user_bookings' => $userBookings,
            'slot_names' => $slotNames,
            'blocked_slots' => $blockedSlots,
            'week_offset' => $weekOffset,
        ]);
    }
}
