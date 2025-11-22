<?php

namespace SportOase\Service;

use SportOase\Entity\Booking;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportService
{
    public function exportToCSV(array $bookings): string
    {
        $csv = fopen('php://temp', 'r+');
        
        fputcsv($csv, [
            'Datum',
            'Wochentag',
            'Stunde',
            'Lehrer',
            'Klasse',
            'Angebot Typ',
            'Angebot',
            'Anzahl Schüler',
            'Erstellt am'
        ]);
        
        foreach ($bookings as $booking) {
            $studentsJson = $booking->getStudentsJson();
            $students = is_string($studentsJson) ? json_decode($studentsJson, true) : $studentsJson;
            $studentCount = is_array($students) ? count($students) : 0;
            
            fputcsv($csv, [
                $booking->getDate()->format('d.m.Y'),
                $booking->getWeekday(),
                $booking->getPeriod() . '. Stunde',
                $booking->getTeacherName(),
                $booking->getTeacherClass(),
                $booking->getOfferType(),
                $booking->getOfferLabel(),
                $studentCount,
                $booking->getCreatedAt() ? $booking->getCreatedAt()->format('d.m.Y H:i') : ''
            ]);
        }
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        return "\xEF\xBB\xBF" . $content;
    }

    public function exportToPDF(array $bookings): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SportOase Buchungen</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { color: #333; text-align: center; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #4A90E2; color: white; padding: 8px; text-align: left; font-size: 10px; }
        td { border: 1px solid #ddd; padding: 6px; font-size: 9px; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 15px; }
        .footer { margin-top: 20px; text-align: center; color: #666; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SportOase - Buchungsübersicht</h1>
        <p>Erstellt am: ' . (new \DateTime())->format('d.m.Y H:i') . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Datum</th>
                <th>Wochentag</th>
                <th>Stunde</th>
                <th>Lehrer</th>
                <th>Klasse</th>
                <th>Angebot</th>
                <th>Schüler</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($bookings as $booking) {
            $studentsJson = $booking->getStudentsJson();
            $students = is_string($studentsJson) ? json_decode($studentsJson, true) : $studentsJson;
            $studentCount = is_array($students) ? count($students) : 0;
            
            $html .= '<tr>
                <td>' . htmlspecialchars($booking->getDate()->format('d.m.Y')) . '</td>
                <td>' . htmlspecialchars($booking->getWeekday()) . '</td>
                <td>' . htmlspecialchars($booking->getPeriod() . '. Stunde') . '</td>
                <td>' . htmlspecialchars($booking->getTeacherName()) . '</td>
                <td>' . htmlspecialchars($booking->getTeacherClass()) . '</td>
                <td>' . htmlspecialchars($booking->getOfferLabel()) . '</td>
                <td>' . $studentCount . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>SportOase Buchungssystem - Generiert am ' . (new \DateTime())->format('d.m.Y H:i') . ' Uhr</p>
    </div>
</body>
</html>';
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        return $dompdf->output();
    }
}
