<?php

namespace SportOase\Controller;

use SportOase\Entity\Booking;
use SportOase\Entity\BlockedSlot;
use SportOase\Entity\SlotName;
use SportOase\Entity\User;
use SportOase\Service\BookingService;
use SportOase\Service\ExportService;
use SportOase\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sportoase/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingService $bookingService,
        private ExportService $exportService,
        private AuditService $auditService
    ) {
    }

    #[Route('/', name: 'sportoase_admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $users = $this->entityManager
            ->getRepository(User::class)
            ->findAll();
        
        $bookings = $this->entityManager
            ->getRepository(Booking::class)
            ->findBy([], ['date' => 'DESC', 'period' => 'ASC']);
        
        return $this->render('@SportOase/admin/dashboard.html.twig', [
            'users' => $users,
            'bookings' => $bookings,
        ]);
    }

    #[Route('/booking/{id}/edit', name: 'sportoase_admin_booking_edit', methods: ['GET', 'POST'])]
    public function editBooking(Request $request, Booking $booking): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $this->bookingService->updateBooking($booking, $request->request->all());
                
                $this->addFlash('success', 'Buchung erfolgreich aktualisiert!');
                return $this->redirectToRoute('sportoase_admin_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }
        
        return $this->render('@SportOase/admin/edit_booking.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/slots/manage', name: 'sportoase_admin_manage_slots', methods: ['GET', 'POST'])]
    public function manageSlots(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            if ($action === 'add_slot_name') {
                $slotName = new SlotName();
                $slotName->setWeekday($request->request->get('weekday'));
                $slotName->setPeriod((int) $request->request->get('period'));
                $slotName->setLabel($request->request->get('label'));
                
                $this->entityManager->persist($slotName);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Slot-Name erfolgreich hinzugefügt!');
            } elseif ($action === 'block_slot') {
                $blockedSlot = new BlockedSlot();
                $blockedSlot->setDate(new \DateTime($request->request->get('date')));
                $blockedSlot->setPeriod((int) $request->request->get('period'));
                $blockedSlot->setWeekday($request->request->get('weekday'));
                $blockedSlot->setReason($request->request->get('reason', 'Beratung'));
                $blockedSlot->setBlockedBy($this->getUser());
                
                $this->entityManager->persist($blockedSlot);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Slot erfolgreich blockiert!');
            } elseif ($action === 'edit_slot_name') {
                $slotNameId = (int) $request->request->get('slot_name_id');
                $slotName = $this->entityManager->getRepository(SlotName::class)->find($slotNameId);
                
                if ($slotName) {
                    $slotName->setLabel($request->request->get('label'));
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Slot-Name erfolgreich aktualisiert!');
                }
            }
            
            return $this->redirectToRoute('sportoase_admin_manage_slots');
        }
        
        $slotNames = $this->entityManager
            ->getRepository(SlotName::class)
            ->findAll();
        
        $blockedSlots = $this->entityManager
            ->getRepository(BlockedSlot::class)
            ->findBy([], ['date' => 'DESC']);
        
        return $this->render('@SportOase/admin/manage_slots.html.twig', [
            'slot_names' => $slotNames,
            'blocked_slots' => $blockedSlots,
        ]);
    }

    #[Route('/slots/slot-name/{id}/delete', name: 'sportoase_admin_delete_slot_name', methods: ['POST'])]
    public function deleteSlotName(Request $request, SlotName $slotName): Response
    {
        if ($this->isCsrfTokenValid('delete-slot-name-' . $slotName->getId(), $request->request->get('_token'))) {
            $slotData = [
                'weekday' => $slotName->getWeekday(),
                'period' => $slotName->getPeriod(),
                'label' => $slotName->getLabel()
            ];
            
            $this->auditService->log(
                'SlotName',
                $slotName->getId(),
                'delete',
                $this->getUser(),
                $slotData,
                'Slot-Name gelöscht: ' . $slotName->getLabel()
            );
            
            $this->entityManager->remove($slotName);
            $this->entityManager->flush();
            $this->addFlash('success', 'Slot-Name erfolgreich gelöscht!');
        }
        
        return $this->redirectToRoute('sportoase_admin_manage_slots');
    }

    #[Route('/slots/blocked-slot/{id}/delete', name: 'sportoase_admin_delete_blocked_slot', methods: ['POST'])]
    public function deleteBlockedSlot(Request $request, BlockedSlot $blockedSlot): Response
    {
        if ($this->isCsrfTokenValid('delete-blocked-slot-' . $blockedSlot->getId(), $request->request->get('_token'))) {
            $blockData = [
                'date' => $blockedSlot->getDate()->format('d.m.Y'),
                'period' => $blockedSlot->getPeriod(),
                'reason' => $blockedSlot->getReason()
            ];
            
            $this->auditService->log(
                'BlockedSlot',
                $blockedSlot->getId(),
                'delete',
                $this->getUser(),
                $blockData,
                'Blockierung aufgehoben für ' . $blockedSlot->getDate()->format('d.m.Y') . ', Stunde ' . $blockedSlot->getPeriod()
            );
            
            $this->entityManager->remove($blockedSlot);
            $this->entityManager->flush();
            $this->addFlash('success', 'Blockierung erfolgreich aufgehoben!');
        }
        
        return $this->redirectToRoute('sportoase_admin_manage_slots');
    }

    #[Route('/users/manage', name: 'sportoase_admin_manage_users', methods: ['GET', 'POST'])]
    public function manageUsers(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $userId = (int) $request->request->get('user_id');
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            
            if ($user && $action === 'toggle_active') {
                $oldStatus = $user->isActive();
                $user->setIsActive(!$user->isActive());
                $user->setUpdatedAt(new \DateTime());
                $this->entityManager->flush();
                
                $this->auditService->log(
                    'User',
                    $user->getId(),
                    'update',
                    $this->getUser(),
                    ['is_active' => ['old' => $oldStatus, 'new' => $user->isActive()]],
                    'Benutzer ' . ($user->isActive() ? 'aktiviert' : 'deaktiviert') . ': ' . $user->getUsername()
                );
                
                $status = $user->isActive() ? 'aktiviert' : 'deaktiviert';
                $this->addFlash('success', "Benutzer erfolgreich {$status}!");
            }
            
            return $this->redirectToRoute('sportoase_admin_manage_users');
        }
        
        $users = $this->entityManager
            ->getRepository(User::class)
            ->findBy([], ['username' => 'ASC']);
        
        return $this->render('@SportOase/admin/manage_users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/bookings/search', name: 'sportoase_admin_search_bookings', methods: ['GET'])]
    public function searchBookings(Request $request): Response
    {
        $searchTerm = $request->query->get('q', '');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        
        $queryBuilder = $this->entityManager
            ->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->leftJoin('b.teacher', 't')
            ->orderBy('b.date', 'DESC')
            ->addOrderBy('b.period', 'ASC');
        
        if ($searchTerm) {
            $queryBuilder->andWhere('b.teacherName LIKE :search OR b.teacherClass LIKE :search OR b.offerLabel LIKE :search OR t.username LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        if ($dateFrom) {
            $queryBuilder->andWhere('b.date >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        
        if ($dateTo) {
            $queryBuilder->andWhere('b.date <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo));
        }
        
        $bookings = $queryBuilder->getQuery()->getResult();
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        return $this->render('@SportOase/admin/dashboard.html.twig', [
            'bookings' => $bookings,
            'users' => $users,
            'search_term' => $searchTerm,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }

    #[Route('/statistics', name: 'sportoase_admin_statistics', methods: ['GET'])]
    public function statistics(): Response
    {
        $bookingRepo = $this->entityManager->getRepository(Booking::class);
        $userRepo = $this->entityManager->getRepository(User::class);
        
        $totalBookings = count($bookingRepo->findAll());
        $totalUsers = count($userRepo->findAll());
        $activeUsers = count($userRepo->findBy(['isActive' => true]));
        
        $currentWeek = new \DateTime('this week');
        $nextWeek = new \DateTime('next week');
        $bookingsThisWeek = count($bookingRepo->createQueryBuilder('b')
            ->where('b.date >= :start')
            ->andWhere('b.date < :end')
            ->setParameter('start', $currentWeek)
            ->setParameter('end', $nextWeek)
            ->getQuery()
            ->getResult());
        
        $bookingsByDay = $bookingRepo->createQueryBuilder('b')
            ->select('b.weekday, COUNT(b.id) as count')
            ->groupBy('b.weekday')
            ->getQuery()
            ->getResult();
        
        $bookingsByPeriod = $bookingRepo->createQueryBuilder('b')
            ->select('b.period, COUNT(b.id) as count')
            ->groupBy('b.period')
            ->orderBy('b.period', 'ASC')
            ->getQuery()
            ->getResult();
        
        $topTeachers = $bookingRepo->createQueryBuilder('b')
            ->select('b.teacherName, COUNT(b.id) as count')
            ->groupBy('b.teacherName')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        return $this->render('@SportOase/admin/statistics.html.twig', [
            'total_bookings' => $totalBookings,
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'bookings_this_week' => $bookingsThisWeek,
            'bookings_by_day' => $bookingsByDay,
            'bookings_by_period' => $bookingsByPeriod,
            'top_teachers' => $topTeachers,
        ]);
    }

    #[Route('/export/csv', name: 'sportoase_admin_export_csv', methods: ['GET'])]
    public function exportCSV(Request $request): Response
    {
        $searchTerm = $request->query->get('q', '');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        
        $queryBuilder = $this->entityManager
            ->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->orderBy('b.date', 'DESC')
            ->addOrderBy('b.period', 'ASC');
        
        if ($searchTerm) {
            $queryBuilder->andWhere('b.teacherName LIKE :search OR b.offerLabel LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        if ($dateFrom) {
            $queryBuilder->andWhere('b.date >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        
        if ($dateTo) {
            $queryBuilder->andWhere('b.date <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo));
        }
        
        $bookings = $queryBuilder->getQuery()->getResult();
        $csvContent = $this->exportService->exportToCSV($bookings);
        
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'sportoase_buchungen_' . date('Y-m-d') . '.csv'
        ));
        
        return $response;
    }

    #[Route('/export/pdf', name: 'sportoase_admin_export_pdf', methods: ['GET'])]
    public function exportPDF(Request $request): Response
    {
        $searchTerm = $request->query->get('q', '');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        
        $queryBuilder = $this->entityManager
            ->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->orderBy('b.date', 'DESC')
            ->addOrderBy('b.period', 'ASC');
        
        if ($searchTerm) {
            $queryBuilder->andWhere('b.teacherName LIKE :search OR b.offerLabel LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        if ($dateFrom) {
            $queryBuilder->andWhere('b.date >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        
        if ($dateTo) {
            $queryBuilder->andWhere('b.date <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo));
        }
        
        $bookings = $queryBuilder->getQuery()->getResult();
        $pdfContent = $this->exportService->exportToPDF($bookings);
        
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'sportoase_buchungen_' . date('Y-m-d') . '.pdf'
        ));
        
        return $response;
    }
}
