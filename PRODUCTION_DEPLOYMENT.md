# SportOase IServ-Modul - Produktions-Deployment

## 🎯 Übersicht

Das SportOase-Modul ist jetzt bereit für den Live-Betrieb auf IServ. Alle Funktionen aus der Test-Umgebung wurden in das IServ-Symfony-Bundle integriert.

## ✅ Implementierte Features

### Kern-Funktionen
- ✅ Buchungssystem mit Wochenansicht (nur Mo-Fr)
- ✅ Feste Angebote mit benutzerdefinierten Namen
- ✅ Automatischer Wochen-Sprung ab Freitag 0 Uhr
- ✅ 60-Minuten-Vorlauf-Regel
- ✅ Wochenend-Blockierung (Sa/So nicht verfügbar)
- ✅ Slot-Sperrung durch Administratoren
- ✅ Google Calendar Integration (optional)
- ✅ E-Mail-Benachrichtigungen
- ✅ Admin-Panel mit vollständiger Verwaltung
- ✅ Responsive Design mit festen Slot-Größen
- ✅ Kontaktbox mit Telefon & E-Mail

### Neue Entities
- `FixedOfferName`: Verwaltung benutzerdefinierter Namen
- `FixedOfferPlacement`: Definition fester Angebote pro Wochentag/Stunde

### Services
- `ConfigService`: Zentrale Konfiguration, Perioden, Module, Fixed Offers
- `BookingService`: Buchungslogik mit vollständiger Validierung
- `GoogleCalendarService`: Google Calendar Integration (graceful degradation)
- `EmailService`: E-Mail-Benachrichtigungen
- `AuditService`: Audit-Trail für Änderungen

## 📦 Deployment-Schritte

### 1. Vorbereitung

```bash
# Ins Projekt-Verzeichnis wechseln
cd /pfad/zum/sportoase

# Composer-Abhängigkeiten installieren
composer install --no-dev --optimize-autoloader

# NPM-Abhängigkeiten installieren
npm install

# Assets kompilieren
npm run build
```

### 2. Datenbank-Migration

```bash
# Migrationen ausführen
php bin/console doctrine:migrations:migrate --no-interaction

# Prüfen ob alle Tabellen erstellt wurden
php bin/console doctrine:schema:validate
```

**Erwartete Tabellen**:
- sportoase_users
- sportoase_bookings
- sportoase_blocked_slots
- sportoase_slot_names
- sportoase_notifications
- sportoase_fixed_offer_names ⭐ NEU
- sportoase_fixed_offer_placements ⭐ NEU
- sportoase_audit_log
- sportoase_system_config

### 3. Umgebungsvariablen konfigurieren

**Datei**: `.env` oder `.env.local`

```env
# Datenbank (von IServ bereitgestellt)
DATABASE_URL=postgresql://sportoase:password@localhost/iserv_db

# E-Mail (Gmail SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=sportoase.kgs@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=sportoase.kgs@gmail.com

# Google Calendar (optional)
GOOGLE_CALENDAR_ID=your-calendar-id@group.calendar.google.com

# Symfony
APP_ENV=prod
APP_SECRET=your-random-secret-key
```

### 4. Google Calendar Setup (Optional)

Falls Google Calendar Integration gewünscht:

1. **Service Account erstellen**:
   - Google Cloud Console öffnen
   - Projekt erstellen/auswählen
   - "APIs & Services" > "Credentials"
   - "Create Credentials" > "Service Account"
   - JSON-Key herunterladen

2. **Credentials speichern**:
   ```bash
   cp google-credentials.json config/google-credentials.json
   chmod 600 config/google-credentials.json
   ```

3. **Kalender freigeben**:
   - Google Calendar öffnen
   - Kalender-Einstellungen
   - Service Account E-Mail hinzufügen (Berechtigung: "Änderungen an Terminen vornehmen")

4. **Calendar ID in `.env` setzen**

**Wichtig**: Ohne Google Credentials funktioniert die App trotzdem - Calendar-Features werden einfach deaktiviert (graceful degradation).

### 5. Debian-Paket erstellen

```bash
# Berechtigungen setzen
chmod +x debian/rules

# Paket bauen
dpkg-deb --build . sportoase-module.deb

# Auf IServ-Server kopieren
scp sportoase-module.deb root@iserv-server:/tmp/
```

### 6. Auf IServ installieren

```bash
# Auf IServ-Server
ssh root@iserv-server

# Paket installieren
dpkg -i /tmp/sportoase-module.deb

# IServ-Dienste neu starten
systemctl restart iserv-portal
systemctl restart iserv-web

# Modul aktivieren
iserv-admin-modules enable sportoase
```

### 7. Prüfung

1. **IServ-Portal öffnen**: https://iserv-server.de
2. **Als Admin anmelden**
3. **SportOase-Modul öffnen**
4. **Testen**:
   - Dashboard lädt
   - Wochenansicht zeigt Mo-Fr
   - Feste Angebote werden gelb angezeigt
   - Buchung erstellen funktioniert
   - Kontaktbox ist sichtbar

## 🔧 Post-Deployment-Konfiguration

### Admin-Panel-Aufgaben

1. **Feste Angebote umbenennen** (optional):
   - Admin-Panel > Feste Angebote
   - Angebote nach Wunsch umbenennen
   - Z.B. "Wochenstart Warm-Up" → "Montagsaktivierung"

2. **Feste Angebote verschieben** (optional):
   - Admin-Panel > Feste Angebote verwalten
   - Angebote auf andere Wochentage/Stunden verschieben
   - Oder löschen/hinzufügen

3. **Test-Buchung erstellen**:
   - Als Lehrer anmelden
   - Slot buchen
   - Prüfen ob E-Mail versendet wird
   - Prüfen ob Google Calendar Eintrag erstellt wird (falls aktiviert)

## 📁 Wichtige Dateien

### Entities (src/Entity/)
- `Booking.php`: Buchungen
- `BlockedSlot.php`: Gesperrte Slots
- `FixedOfferName.php`: ⭐ NEU - Benutzerdefinierte Namen
- `FixedOfferPlacement.php`: ⭐ NEU - Feste Angebots-Platzierungen

### Services (src/Service/)
- `ConfigService.php`: ⭐ ERWEITERT - Zentrale Konfiguration
- `BookingService.php`: Buchungslogik (siehe MIGRATION_FROM_TEST.md für Erweiterungen)
- `GoogleCalendarService.php`: Google Calendar Integration

### Controller (src/Controller/)
- `DashboardController.php`: Dashboard mit Wochenansicht
- `AdminController.php`: Admin-Panel (siehe MIGRATION_FROM_TEST.md für neue Routes)
- `BookingController.php`: Buchungserstellung/-bearbeitung

### Migrations (migrations/)
- `Version001CreateInitialSchema.php`: Basis-Tabellen
- `Version002AddAuditLog.php`: Audit-Trail
- `Version003AddFixedOffers.php`: ⭐ NEU - Feste Angebote

### Templates (templates/sportoase/)
- `dashboard.html.twig`: Wochenansicht (siehe MIGRATION_FROM_TEST.md für CSS & Kontaktbox)
- `admin/*.html.twig`: Admin-Templates

## 🚨 Troubleshooting

### Problem: Migration schlägt fehl
```bash
# Manuell überprüfen
php bin/console doctrine:schema:update --dump-sql

# Falls nötig, manuell ausführen
php bin/console doctrine:schema:update --force
```

### Problem: Assets werden nicht geladen
```bash
# Assets neu kompilieren
npm run build

# Cache leeren
php bin/console cache:clear --env=prod
```

### Problem: Google Calendar funktioniert nicht
- ✅ Check: `config/google-credentials.json` existiert?
- ✅ Check: Service Account hat Kalender-Zugriff?
- ✅ Check: `GOOGLE_CALENDAR_ID` in `.env` gesetzt?
- ℹ️ **App funktioniert auch ohne Calendar** (graceful degradation)

### Problem: E-Mails werden nicht versendet
- ✅ Check: SMTP-Credentials korrekt?
- ✅ Check: Gmail "App-Passwort" verwendet (nicht normales Passwort)?
- ✅ Check: Firewall erlaubt Port 587?

### Problem: Feste Angebote werden nicht angezeigt
```bash
# Prüfen ob Migration ausgeführt wurde
psql -d iserv_db -c "SELECT * FROM sportoase_fixed_offer_placements;"

# Falls leer, Migration erneut ausführen
php bin/console doctrine:migrations:execute Version003AddFixedOffers --up
```

## 📞 Support & Kontakt

**Bei Problemen oder Fragen**:
- E-Mail: morelli.maurizio@kgs-pattensen.de
- Telefon: 0151 40349764

## 📚 Weitere Dokumentation

- `MIGRATION_FROM_TEST.md`: Detaillierte Implementierungs-Anleitung
- `ISERV_DEPLOYMENT_GUIDE.md`: IServ-spezifische Deployment-Infos
- `DATABASE_SETUP.md`: Datenbank-Schema-Dokumentation
- `EMAIL_SETUP.md`: E-Mail-Konfiguration

## ✅ Checkliste vor Go-Live

- [ ] Alle Composer-Abhängigkeiten installiert
- [ ] Alle NPM-Assets kompiliert
- [ ] Datenbank-Migrationen ausgeführt
- [ ] Umgebungsvariablen konfiguriert (.env)
- [ ] SMTP-Zugangsdaten getestet
- [ ] Google Calendar konfiguriert (optional)
- [ ] Debian-Paket erstellt
- [ ] Auf IServ installiert
- [ ] Modul aktiviert
- [ ] Test-Buchung erfolgreich
- [ ] E-Mail-Versand funktioniert
- [ ] Admin-Panel zugänglich
- [ ] Feste Angebote werden angezeigt
- [ ] Kontaktbox sichtbar
- [ ] Responsive Design auf Tablet/Handy getestet

---

**Version**: 1.0.0  
**Datum**: 22. November 2025  
**Status**: ✅ Produktionsbereit
