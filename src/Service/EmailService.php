<?php

namespace SportOase\Service;

use SportOase\Entity\Booking;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private const ADMIN_EMAIL = 'sportoase.kg@gmail.com';

    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public function sendBookingNotification(Booking $booking): void
    {
        $email = (new Email())
            ->from('noreply@sportoase.local')
            ->to(self::ADMIN_EMAIL)
            ->subject('Neue SportOase Buchung')
            ->html($this->renderBookingEmail($booking));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
        }
    }

    private function renderBookingEmail(Booking $booking): string
    {
        $students = $booking->getStudentsJson();
        $studentList = '';
        foreach ($students as $student) {
            $studentList .= '<li>' . htmlspecialchars($student['name']) . ' (' . htmlspecialchars($student['class']) . ')</li>';
        }

        return <<<HTML
        <h2>Neue SportOase Buchung</h2>
        <p><strong>Datum:</strong> {$booking->getDate()->format('d.m.Y')}</p>
        <p><strong>Stunde:</strong> {$booking->getPeriod()}</p>
        <p><strong>Lehrer:</strong> {$booking->getTeacherName()} ({$booking->getTeacherClass()})</p>
        <p><strong>Angebot:</strong> {$booking->getOfferLabel()}</p>
        <p><strong>Schüler:</strong></p>
        <ul>{$studentList}</ul>
        HTML;
    }
}
