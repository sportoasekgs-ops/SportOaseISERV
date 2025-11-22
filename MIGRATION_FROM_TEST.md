# Migration der Test-Umgebung in das IServ-Modul

## Übersicht

Dieses Dokument beschreibt, welche Funktionen aus der Test-Umgebung (`test/`) bereits in das IServ-Modul integriert wurden und welche noch zu implementieren sind.

## ✅ Bereits implementiert

### 1. Neue Entities
- **FixedOfferName** (`src/Entity/FixedOfferName.php`)
  - Verwaltet benutzerdefinierte Namen für feste Angebote
  - Felder: `offer_key`, `default_name`, `custom_name`

- **FixedOfferPlacement** (`src/Entity/FixedOfferPlacement.php`)
  - Definiert, an welchen Wochentagen/Stunden feste Angebote stattfinden
  - Felder: `weekday`, `period`, `offer_name`

### 2. Datenbank-Migration
- **Version003AddFixedOffers** (`migrations/Version003AddFixedOffers.php`)
  - Erstellt Tabellen für feste Angebote
  - Fügt Standard-Angebote ein:
    - Aktivierung
    - Regulation/Entspannung
    - Konflikt-Reset
    - Turnen/flexibel
    - Wochenstart Warm-Up
  - Definiert Standard-Platzierungen (Mo-Fr, verschiedene Stunden)

### 3. ConfigService
- **Erweitert** (`src/Service/ConfigService.php`)
  - `getPeriods()`: Stundenzeiten
  - `getFreeModules()`: Alle buchbaren Module
  - `getFixedOfferPlacements()`: Feste Angebote aus DB
  - `getOfferCustomName()`: Benutzerdefinierte Namen
  - `getFixedOfferKey()`: Original-Name des festen Angebots
  - `getFixedOfferDisplayName()`: Anzeigename mit Custom-Name
  - `isSlotBookable()`: Prüft Wochenende + 60-Min-Regel
  - `hasFixedOffer()`: Prüft ob Slot festes Angebot hat
  - `getContactEmail()` / `getContactPhone()`: Kontaktdaten

## 🔄 Noch zu implementieren

### 1. BookingService erweitern
**Datei**: `src/Service/BookingService.php`

**Hinzuzufügende Validierungen**:
```php
public function validateBooking(\DateTime $date, int $period, array $data): void
{
    // 1. Modul-Whitelist prüfen
    if (!in_array($data['offer_type'], $this->configService->getFreeModules())) {
        throw new \InvalidArgumentException('Ungültiges Modul gewählt.');
    }
    
    // 2. Slot buchbar prüfen (Wochenende + Zeitvorlauf)
    if (!$this->configService->isSlotBookable($date, $period)) {
        throw new \InvalidArgumentException('Dieser Slot ist nicht buchbar.');
    }
    
    // 3. Bei festem Angebot: nur dieses Modul buchbar
    $fixedOfferKey = $this->configService->getFixedOfferKey($date, $period);
    if ($fixedOfferKey && $data['offer_type'] !== $fixedOfferKey) {
        throw new \InvalidArgumentException(
            'Dieser Slot hat ein festes Angebot. Nur ' . $fixedOfferKey . ' ist buchbar.'
        );
    }
    
    // 4. Gesperrte Slots prüfen
    $blocked = $this->entityManager
        ->getRepository(BlockedSlot::class)
        ->findOneBy(['date' => $date, 'period' => $period]);
    if ($blocked) {
        throw new \InvalidArgumentException('Dieser Slot ist gesperrt.');
    }
    
    // 5. Doppelbuchung prüfen
    $existing = $this->entityManager
        ->getRepository(Booking::class)
        ->findOneBy(['date' => $date, 'period' => $period]);
    if ($existing) {
        throw new \InvalidArgumentException('Dieser Slot ist bereits gebucht.');
    }
}
```

**Auto-Week-Jump hinzufügen**:
```php
public function getWeekData(int $weekOffset = 0): array
{
    $now = new \DateTime();
    $dayOfWeek = (int)$now->format('N'); // 1 = Monday, 7 = Sunday
    
    // Ab Freitag 0 Uhr zur nächsten Woche springen
    if ($dayOfWeek >= 5) {
        $monday = new \DateTime('next monday');
    } else {
        $monday = new \DateTime('monday this week');
    }
    
    $monday->modify($weekOffset > 0 ? "+{$weekOffset} week" : "{$weekOffset} week");
    
    // Nur Mo-Fr, NICHT Sa-So
    $weekDays = [];
    for ($i = 0; $i < 5; $i++) {
        $date = clone $monday;
        $date->modify("+{$i} days");
        $weekDays[] = [
            'date' => $date,
            'weekday' => $date->format('l'),
            'formatted' => $date->format('d.m.Y'),
        ];
    }
    
    return [
        'days' => $weekDays,
        'periods' => $this->configService->getPeriods(),
        'start_date' => $monday,
    ];
}
```

### 2. DashboardController erweitern
**Datei**: `src/Controller/DashboardController.php`

**Hinzufügen**: ConfigService injizieren und Fixed Offers an Template übergeben

```php
public function __construct(
    private EntityManagerInterface $entityManager,
    private BookingService $bookingService,
    private ConfigService $configService  // NEU
) {}

#[Route('/', name: 'sportoase_dashboard', methods: ['GET'])]
public function dashboard(Request $request): Response
{
    $user = $this->getUser();
    $weekOffset = (int) $request->query->get('week', 0);
    $weekData = $this->bookingService->getWeekData($weekOffset);
    
    // Fixed offers für die Anzeige
    $fixedOfferPlacements = $this->configService->getFixedOfferPlacements();
    
    return $this->render('@SportOase/dashboard.html.twig', [
        'user' => $user,
        'week_data' => $weekData,
        'periods' => $this->configService->getPeriods(),
        'free_modules' => $this->configService->getFreeModules(),
        'fixed_offer_placements' => $fixedOfferPlacements,
        'config_service' => $this->configService,  // Für Template-Helpers
        'week_offset' => $weekOffset,
    ]);
}
```

### 3. AdminController erweitern
**Datei**: `src/Controller/AdminController.php`

**Neue Routes hinzufügen**:
```php
#[Route('/admin/fixed-offers', name: 'sportoase_admin_fixed_offers')]
public function manageFixedOffers(): Response
{
    $offerNames = $this->entityManager
        ->getRepository(FixedOfferName::class)
        ->findAll();
    
    $placements = $this->entityManager
        ->getRepository(FixedOfferPlacement::class)
        ->findAll();
    
    return $this->render('@SportOase/admin/fixed_offers.html.twig', [
        'offer_names' => $offerNames,
        'placements' => $placements,
        'free_modules' => $this->configService->getFreeModules(),
    ]);
}

#[Route('/admin/fixed-offers/update-name', name: 'sportoase_admin_update_offer_name', methods: ['POST'])]
public function updateOfferName(Request $request): Response
{
    $offerKey = $request->request->get('offer_key');
    $customName = $request->request->get('custom_name');
    
    $offerName = $this->entityManager
        ->getRepository(FixedOfferName::class)
        ->findOneBy(['offerKey' => $offerKey]);
    
    if ($offerName) {
        $offerName->setCustomName($customName);
        $this->entityManager->flush();
    }
    
    return $this->redirectToRoute('sportoase_admin_fixed_offers');
}
```

### 4. Dashboard-Template aktualisieren
**Datei**: `templates/sportoase/dashboard.html.twig`

**CSS für feste Slot-Größen hinzufügen**:
```twig
{% block stylesheets %}
{{ parent() }}
<style>
    /* Feste Slot-Größen für konsistente Darstellung */
    table tbody td {
        min-height: 120px;
        height: 120px;
        vertical-align: middle;
    }
    
    table tbody td > div,
    table tbody td > button {
        min-height: 100px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
</style>
{% endblock %}
```

**Kontaktbox unter Wochenplan**:
```twig
{# Nach dem Wochenplan #}
<div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg shadow-sm p-6 mt-6">
    <div class="flex items-start gap-4">
        <div class="text-3xl">📞</div>
        <div>
            <h3 class="text-lg font-bold text-blue-900 mb-2">Kontakt bei dringenden Anliegen</h3>
            <p class="text-gray-700 mb-3">
                Bei dringenden Anliegen bin ich direkt erreichbar:
            </p>
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-blue-600">📧</span>
                    <a href="mailto:{{ config_service.contactEmail }}" class="text-blue-600 hover:text-blue-800 underline font-medium">
                        {{ config_service.contactEmail }}
                    </a>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-blue-600">📱</span>
                    <a href="tel:{{ config_service.contactPhone|replace({' ': ''}) }}" class="text-blue-600 hover:text-blue-800 underline font-medium">
                        {{ config_service.contactPhone }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Slots mit festen Angeboten**:
```twig
{% for period_num, period_time in periods %}
    <tr>
        <td>{{ period_num }}. Stunde<br><small>{{ period_time.label }}</small></td>
        {% for day in week_data.days %}
            {% set date_obj = day.date %}
            {% set weekday = date_obj.format('N') %}
            
            {# Fixed Offer prüfen #}
            {% set fixed_offer_key = config_service.getFixedOfferKey(date_obj, period_num) %}
            {% set fixed_offer_display = config_service.getFixedOfferDisplayName(date_obj, period_num) %}
            {% set is_bookable = config_service.isSlotBookable(date_obj, period_num) %}
            
            <td>
                {% if fixed_offer_key and is_bookable %}
                    {# Festes Angebot - gelb, aber buchbar #}
                    <button onclick="openBookingModal('{{ date_obj.format('Y-m-d') }}', {{ period_num }}, '{{ fixed_offer_key }}')"
                            class="w-full bg-yellow-50 hover:bg-yellow-100 border border-yellow-300 rounded-lg p-3 text-sm transition">
                        <div class="font-semibold text-yellow-800">⭐ {{ fixed_offer_display }}</div>
                        <div class="text-xs text-yellow-700 mt-1">+ Buchen</div>
                    </button>
                {% elseif is_bookable %}
                    {# Freier Slot - grün #}
                    <button onclick="openBookingModal('{{ date_obj.format('Y-m-d') }}', {{ period_num }})"
                            class="w-full bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-3 text-sm text-green-700 font-medium transition">
                        + Buchen
                    </button>
                {% else %}
                    {# Nicht verfügbar - grau #}
                    <div class="bg-gray-100 border border-gray-300 rounded-lg p-3 text-center">
                        <div class="text-xs text-gray-500">Nicht verfügbar</div>
                    </div>
                {% endif %}
            </td>
        {% endfor %}
    </tr>
{% endfor %}
```

### 5. GoogleCalendarService mit Graceful Degradation
**Datei**: `src/Service/GoogleCalendarService.php`

**Prüfung am Anfang**:
```php
private $enabled = false;

public function __construct()
{
    // Prüfen ob Google API Client verfügbar ist
    if (!class_exists('Google_Client')) {
        $this->enabled = false;
        return;
    }
    
    // Prüfen ob Credentials vorhanden
    $credentialsPath = __DIR__ . '/../../config/google-credentials.json';
    if (!file_exists($credentialsPath)) {
        $this->enabled = false;
        return;
    }
    
    // Google Client initialisieren
    try {
        $this->client = new \Google_Client();
        $this->client->setAuthConfig($credentialsPath);
        $this->service = new \Google_Service_Calendar($this->client);
        $this->enabled = true;
    } catch (\Exception $e) {
        $this->enabled = false;
    }
}

public function isEnabled(): bool
{
    return $this->enabled;
}

// In allen Methoden:
public function createEvent(Booking $booking): ?string
{
    if (!$this->enabled) {
        return null;
    }
    // ... Rest der Implementierung
}
```

## 📋 Deployment-Checkliste

### 1. Migration ausführen
```bash
php bin/console doctrine:migrations:migrate
```

### 2. Assets kompilieren
```bash
npm install
npm run build
```

### 3. Berechtigungen setzen
```bash
chmod +x debian/rules
```

### 4. Debian-Paket erstellen
```bash
dpkg-deb --build . sportoase-module.deb
```

### 5. Auf IServ installieren
```bash
sudo dpkg -i sportoase-module.deb
sudo systemctl restart iserv
```

## 🔧 Konfiguration

### Umgebungsvariablen (`.env`)
```env
DATABASE_URL=postgresql://user:pass@localhost/iserv
GOOGLE_CALENDAR_ID=deine-calendar-id@group.calendar.google.com
SMTP_USER=sportoase.kgs@gmail.com
SMTP_PASS=dein-smtp-passwort
```

### Google Calendar Setup (optional)
1. Service Account in Google Cloud Console erstellen
2. JSON-Datei als `config/google-credentials.json` speichern
3. Service Account Zugriff auf Kalender geben
4. `GOOGLE_CALENDAR_ID` in `.env` setzen

## ⚠️ Wichtige Hinweise

1. **Feste Angebote sind ANZEIGE-Elemente**: Die gelben Slots zeigen an, dass normalerweise ein bestimmtes Angebot stattfindet, aber Lehrer können den Slot trotzdem buchen - müssen dann aber genau dieses Modul wählen.

2. **Wochenend-Logik**: Die App zeigt nur Mo-Fr. Ab Freitag 0 Uhr springt die Ansicht automatisch zur nächsten Woche.

3. **60-Minuten-Regel**: Slots können nur bis 60 Minuten vor Beginn gebucht werden.

4. **Module-Whitelist**: Nur die 5 definierten Module können gebucht werden. Backend validiert dies.

5. **Calendar Integration**: Funktioniert auch ohne Google Credentials (graceful degradation).

## 📞 Support
Bei Fragen: morelli.maurizio@kgs-pattensen.de | 0151 40349764
