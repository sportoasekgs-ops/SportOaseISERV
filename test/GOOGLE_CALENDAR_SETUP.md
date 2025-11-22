# Google Calendar Integration Setup

Die SportOase-App kann automatisch Kalendereinträge für Buchungen in Google Calendar erstellen.

## Voraussetzungen

- Ein Google-Account mit Zugriff auf Google Calendar
- Ein Google Cloud Project mit aktivierter Calendar API
- Service Account Credentials

## Setup-Schritte

### 1. Google Cloud Project erstellen

1. Gehe zu [Google Cloud Console](https://console.cloud.google.com/)
2. Erstelle ein neues Projekt oder wähle ein bestehendes aus
3. Aktiviere die **Google Calendar API**:
   - Navigation: APIs & Services → Library
   - Suche nach "Google Calendar API"
   - Klicke auf "Enable"

### 2. Service Account erstellen

1. Navigation: APIs & Services → Credentials
2. Klicke auf "Create Credentials" → "Service Account"
3. Name: `sportoase-calendar-service`
4. Rolle: Keine spezielle Rolle nötig
5. Klicke auf "Done"

### 3. Service Account Key erstellen

1. Klicke auf den erstellten Service Account
2. Gehe zum Tab "Keys"
3. Klicke auf "Add Key" → "Create new key"
4. Wähle **JSON** Format
5. Die Datei wird heruntergeladen (z.B. `sportoase-calendar-xxxxx.json`)
6. **Benenne die Datei um zu**: `google-credentials.json`
7. **Speichere sie im `test/` Verzeichnis**

⚠️ **WICHTIG**: Füge `google-credentials.json` zu `.gitignore` hinzu!

### 4. Kalender-Freigabe einrichten

1. Öffne [Google Calendar](https://calendar.google.com/)
2. Erstelle einen neuen Kalender für SportOase oder wähle einen bestehenden
3. Klicke auf die drei Punkte neben dem Kalender → "Settings and sharing"
4. Scrolle zu "Share with specific people"
5. Klicke auf "Add people"
6. Füge die **Service Account Email** hinzu:
   - Diese findest du in `google-credentials.json` unter `client_email`
   - Beispiel: `sportoase-calendar-service@xxx.iam.gserviceaccount.com`
7. Wähle Permission: **"Make changes to events"**
8. Klicke auf "Send"

### 5. Kalender-ID abrufen

1. In den Kalender-Einstellungen, scrolle zu "Integrate calendar"
2. Kopiere die **Calendar ID** (z.B. `abc123@group.calendar.google.com`)

### 6. Umgebungsvariable setzen

Setze die Kalender-ID als Umgebungsvariable:

```bash
export GOOGLE_CALENDAR_ID="deine-calendar-id@group.calendar.google.com"
```

Oder in Replit:
1. Gehe zu "Secrets" (Schloss-Icon in der Sidebar)
2. Füge ein neues Secret hinzu:
   - Key: `GOOGLE_CALENDAR_ID`
   - Value: Deine Calendar ID

### 7. Testen

1. Starte die App neu
2. Erstelle eine Test-Buchung
3. Überprüfe, ob der Eintrag in Google Calendar erscheint

## Dateistruktur

```
test/
├── google-credentials.json  ← Service Account Key (nicht in Git!)
├── calendar_service.php     ← Calendar Service Klasse
├── dashboard.php            ← Integriert Calendar Events
├── admin.php                ← Integriert Calendar Events
└── .gitignore               ← Füge google-credentials.json hinzu!
```

## Graceful Degradation

Die App funktioniert auch **ohne** Calendar-Integration:

- Wenn `google-credentials.json` fehlt → Calendar-Features deaktiviert
- Wenn `GOOGLE_CALENDAR_ID` nicht gesetzt → Calendar-Features deaktiviert
- Buchungen funktionieren normal, nur ohne Kalendereinträge

## Fehlersuche

### "Calendar integration disabled"

→ `google-credentials.json` oder `GOOGLE_CALENDAR_ID` fehlt

### "Failed to create calendar event"

→ Überprüfe:
- Service Account hat Zugriff auf den Kalender
- Calendar ID ist korrekt
- Calendar API ist aktiviert

### Logs überprüfen

Fehler werden in PHP Error Logs geschrieben:

```bash
tail -f /tmp/logs/SportOase_Test_Environment_*.log
```

## Sicherheit

⚠️ **Niemals** `google-credentials.json` in Git committen!

Füge zu `.gitignore` hinzu:
```
test/google-credentials.json
```

## Event-Details

Jede Buchung erstellt einen Kalendereintrag mit:

- **Titel**: "SportOase: [Modulname]"
- **Beschreibung**: Lehrer, Modul, Schülerliste
- **Datum/Zeit**: Basierend auf Buchungsdatum und Stunde
- **Zeitzone**: Europe/Berlin
- **Farbe**: Blau (ColorID 9)

## Automatische Synchronisation

- **Buchung erstellt** → Kalendereintrag erstellt
- **Buchung bearbeitet** → Kalendereintrag aktualisiert
- **Buchung gelöscht** → Kalendereintrag gelöscht
