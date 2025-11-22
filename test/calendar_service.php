<?php
/**
 * Google Calendar Service for SportOase
 * 
 * Manages Google Calendar integration for automatic event creation,
 * updating, and deletion when bookings are made.
 */

require_once __DIR__ . '/vendor/autoload.php';

class CalendarService {
    private $client;
    private $service;
    private $calendarId;
    private $enabled = false;
    
    public function __construct() {
        try {
            // Check if credentials are available
            $credentialsPath = __DIR__ . '/google-credentials.json';
            $calendarId = getenv('GOOGLE_CALENDAR_ID');
            
            if (!file_exists($credentialsPath) || !$calendarId) {
                // Calendar integration disabled - credentials not configured
                $this->enabled = false;
                error_log('Google Calendar integration disabled: Missing credentials or calendar ID');
                return;
            }
            
            $this->calendarId = $calendarId;
            
            // Initialize Google Client
            $this->client = new Google_Client();
            $this->client->setApplicationName('SportOase Booking System');
            $this->client->setScopes(Google_Service_Calendar::CALENDAR);
            $this->client->setAuthConfig($credentialsPath);
            
            // Initialize Calendar Service
            $this->service = new Google_Service_Calendar($this->client);
            $this->enabled = true;
            
        } catch (Exception $e) {
            error_log('Google Calendar initialization failed: ' . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Check if calendar integration is enabled
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Create a calendar event for a booking
     * 
     * @param array $booking Booking data with date, period, teacher, students, offer
     * @return string|null Event ID if successful, null otherwise
     */
    public function createEvent($booking) {
        if (!$this->enabled) {
            return null;
        }
        
        try {
            // Parse booking data
            $date = $booking['booking_date'];
            $period = $booking['period'];
            $teacher = $booking['teacher_name'];
            $offer = $booking['offer_details'] ?? 'Sportangebot';
            $students = json_decode($booking['students_json'], true);
            
            // Get period times
            $periodTimes = PERIOD_TIMES;
            $timeRange = $periodTimes[$period] ?? '08:00 - 09:00';
            list($startTime, $endTime) = explode(' - ', $timeRange);
            
            // Create DateTime objects
            $startDateTime = new DateTime($date . ' ' . trim($startTime));
            $endDateTime = new DateTime($date . ' ' . trim($endTime));
            
            // Build event description
            $studentList = [];
            foreach ($students as $student) {
                $studentList[] = $student['name'] . ' (' . $student['class'] . ')';
            }
            $description = "Lehrer: " . $teacher . "\n";
            $description .= "Modul: " . $offer . "\n\n";
            $description .= "Schüler:\n" . implode("\n", $studentList);
            
            // Create Google Calendar Event
            $event = new Google_Service_Calendar_Event([
                'summary' => 'SportOase: ' . $offer,
                'description' => $description,
                'start' => [
                    'dateTime' => $startDateTime->format('c'),
                    'timeZone' => 'Europe/Berlin',
                ],
                'end' => [
                    'dateTime' => $endDateTime->format('c'),
                    'timeZone' => 'Europe/Berlin',
                ],
                'colorId' => '9', // Blue color for sport events
            ]);
            
            $createdEvent = $this->service->events->insert($this->calendarId, $event);
            
            error_log('Calendar event created: ' . $createdEvent->getId());
            return $createdEvent->getId();
            
        } catch (Exception $e) {
            error_log('Failed to create calendar event: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update an existing calendar event
     * 
     * @param string $eventId Google Calendar event ID
     * @param array $booking Updated booking data
     * @return bool Success status
     */
    public function updateEvent($eventId, $booking) {
        if (!$this->enabled || !$eventId) {
            return false;
        }
        
        try {
            // Get existing event
            $event = $this->service->events->get($this->calendarId, $eventId);
            
            // Update event details
            $offer = $booking['offer_details'] ?? 'Sportangebot';
            
            // Normalize students_json - handle both JSON string and array
            if (is_string($booking['students_json'])) {
                $students = json_decode($booking['students_json'], true);
            } else {
                // Already an array
                $students = $booking['students_json'];
            }
            
            // Build new description
            $studentList = [];
            foreach ($students as $student) {
                $studentList[] = $student['name'] . ' (' . $student['class'] . ')';
            }
            $description = "Lehrer: " . $booking['teacher_name'] . "\n";
            $description .= "Modul: " . $offer . "\n\n";
            $description .= "Schüler:\n" . implode("\n", $studentList);
            
            $event->setSummary('SportOase: ' . $offer);
            $event->setDescription($description);
            
            $this->service->events->update($this->calendarId, $eventId, $event);
            
            error_log('Calendar event updated: ' . $eventId);
            return true;
            
        } catch (Exception $e) {
            error_log('Failed to update calendar event: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a calendar event
     * 
     * @param string $eventId Google Calendar event ID
     * @return bool Success status
     */
    public function deleteEvent($eventId) {
        if (!$this->enabled || !$eventId) {
            return false;
        }
        
        try {
            $this->service->events->delete($this->calendarId, $eventId);
            error_log('Calendar event deleted: ' . $eventId);
            return true;
            
        } catch (Exception $e) {
            error_log('Failed to delete calendar event: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get a singleton instance of CalendarService
 */
function getCalendarService() {
    static $instance = null;
    if ($instance === null) {
        $instance = new CalendarService();
    }
    return $instance;
}
