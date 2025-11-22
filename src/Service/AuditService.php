<?php

namespace SportOase\Service;

use SportOase\Entity\AuditLog;
use SportOase\Entity\User;

class AuditService
{
    private $entityManager;
    private $requestStack;

    public function __construct($entityManager, $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function log(
        string $entityType,
        int $entityId,
        string $action,
        User $user,
        ?array $changes = null,
        ?string $description = null
    ): void {
        $auditLog = new AuditLog();
        $auditLog->setEntityType($entityType);
        $auditLog->setEntityId($entityId);
        $auditLog->setAction($action);
        $auditLog->setUser($user);
        $auditLog->setUsername($user->getUsername());
        $auditLog->setChanges($changes);
        $auditLog->setDescription($description);
        
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $auditLog->setIpAddress($request->getClientIp());
        }
        
        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }

    public function logBookingCreated(int $bookingId, User $user, array $bookingData): void
    {
        $this->log(
            'Booking',
            $bookingId,
            'created',
            $user,
            null,
            sprintf(
                'Buchung erstellt: %s am %s, Stunde %d',
                $bookingData['offerLabel'] ?? 'Unbekannt',
                $bookingData['date'] ?? 'unbekannt',
                $bookingData['period'] ?? 0
            )
        );
    }

    public function logBookingUpdated(int $bookingId, User $user, array $oldData, array $newData): void
    {
        $changes = [];
        foreach ($newData as $key => $value) {
            if (isset($oldData[$key]) && $oldData[$key] !== $value) {
                $changes[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value
                ];
            }
        }
        
        $this->log(
            'Booking',
            $bookingId,
            'updated',
            $user,
            $changes,
            sprintf('Buchung #%d aktualisiert', $bookingId)
        );
    }

    public function logBookingDeleted(int $bookingId, User $user, array $bookingData): void
    {
        $this->log(
            'Booking',
            $bookingId,
            'deleted',
            $user,
            $bookingData,
            sprintf(
                'Buchung gelöscht: %s am %s',
                $bookingData['offerLabel'] ?? 'Unbekannt',
                $bookingData['date'] ?? 'unbekannt'
            )
        );
    }

    public function getEntityHistory(string $entityType, int $entityId): array
    {
        return $this->entityManager
            ->getRepository(AuditLog::class)
            ->findBy(
                ['entityType' => $entityType, 'entityId' => $entityId],
                ['createdAt' => 'DESC']
            );
    }

    public function getRecentActivity(int $limit = 50): array
    {
        return $this->entityManager
            ->getRepository(AuditLog::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit);
    }
}
