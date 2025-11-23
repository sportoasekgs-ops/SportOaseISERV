# 🚀 SportOase - Schnellstart für IServ (Schritt-für-Schritt)

Diese Anleitung erklärt die Installation so einfach wie möglich, sodass auch Personen ohne technische Vorkenntnisse das Modul auf ihrem IServ-Server installieren können.

---

## ✅ Voraussetzungen

Sie benötigen:
- **IServ-Server** (Version 3.0 oder höher)
- **Admin-Zugang** zum IServ-Server (SSH-Zugriff)
- **ca. 15 Minuten** Zeit

---

## 📦 Schritt 1: Dateien herunterladen

1. Laden Sie das SportOase-Modul herunter (ZIP-Datei von GitHub)
2. Entpacken Sie die ZIP-Datei auf Ihrem Computer
3. Öffnen Sie ein Terminal-Programm:
   - **Windows**: PowerShell oder PuTTY
   - **Mac/Linux**: Terminal

---

## 🔧 Schritt 2: Debian-Paket erstellen

Öffnen Sie das Terminal im entpackten Ordner und führen Sie aus:

```bash
# Paket bauen (dauert ca. 2-3 Minuten)
dpkg-buildpackage -us -uc
```

✅ **Fertig?** Sie sollten jetzt eine Datei namens `iserv-sportoase_1.0.0_all.deb` haben.

---

## 📤 Schritt 3: Paket auf IServ hochladen

### Option A: Mit SCP (empfohlen)
```bash
# Ersetzen Sie "ihr-iserv.de" mit Ihrer IServ-Adresse
scp iserv-sportoase_1.0.0_all.deb admin@ihr-iserv.de:/tmp/
```

### Option B: Mit FileZilla oder WinSCP
1. Verbinden Sie sich zu Ihrem IServ-Server (SFTP)
2. Laden Sie die `.deb`-Datei in den `/tmp/` Ordner hoch

---

## 💻 Schritt 4: Auf IServ-Server anmelden

```bash
# Ersetzen Sie "ihr-iserv.de" mit Ihrer IServ-Adresse
ssh admin@ihr-iserv.de
```

Geben Sie Ihr Admin-Passwort ein.

---

## 📥 Schritt 5: Modul installieren

Führen Sie auf dem IServ-Server aus:

```bash
# Als Root arbeiten
sudo su

# Paket installieren (installiert automatisch alle Abhängigkeiten)
apt install /tmp/iserv-sportoase_1.0.0_all.deb
```

✅ **Fertig!** Das Paket enthält bereits alle vorkompilierten Assets und PHP-Abhängigkeiten.

⏱️ **Dauer**: ca. 30 Sekunden

---

## 🗄️ Schritt 6: Datenbank einrichten

```bash
# Als Root
cd /usr/share/iserv/iservchk/modules/SportOase

# Migrationen ausführen (verwendet IServ's eigenes Console)
sudo -u www-data php /usr/share/iserv/www/iserv console doctrine:migrations:migrate --configuration=migrations/doctrine.yaml --no-interaction
```

✅ **Erfolgreich?** Sie sollten die Meldung sehen: "Migration complete!"

---

## 🔑 Schritt 7: Umgebungsvariablen setzen

Erstellen Sie die Konfigurationsdatei:

```bash
# Datei erstellen
nano /etc/iserv/sportoase.env
```

Fügen Sie folgendes ein (passen Sie die Werte an):

```bash
# IServ OAuth2 Einstellungen
ISERV_BASE_URL=https://ihr-iserv.de
ISERV_CLIENT_ID=sportoase
ISERV_CLIENT_SECRET=IHR-GEHEIMER-SCHLÜSSEL

# E-Mail Einstellungen (optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=sportoase.kg@gmail.com
SMTP_PASS=IHR-EMAIL-PASSWORT
ADMIN_EMAIL=sportoase.kg@gmail.com

# Google Calendar (optional)
GOOGLE_CALENDAR_ID=
GOOGLE_SERVICE_ACCOUNT_JSON=
```

**Speichern**: Drücken Sie `Ctrl+O`, dann `Enter`, dann `Ctrl+X`

---

## 🎯 Schritt 8: Modul aktivieren

### Im IServ-Admin-Panel:

1. Melden Sie sich als Admin im IServ-Webinterface an
2. Gehen Sie zu: **System → Module**
3. Suchen Sie nach "SportOase"
4. Klicken Sie auf **"Aktivieren"**

---

## 🎉 Fertig!

Das Modul ist jetzt installiert! Sie finden es unter:

**https://ihr-iserv.de/sportoase**

### Erste Schritte:

1. **Als Admin anmelden** im IServ
2. **SportOase öffnen** über das Menü
3. **Erste Buchung erstellen** im Dashboard
4. **Einstellungen anpassen** unter Admin → Einstellungen

---

## 🆘 Probleme?

### "Migration failed" Fehler
```bash
# Datenbank zurücksetzen und neu starten
sudo -u www-data php bin/console doctrine:schema:drop --force
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction
```

### "Permission denied" Fehler
```bash
# Berechtigungen setzen
chown -R www-data:www-data /usr/share/iserv/modules/sportoase
chmod -R 755 /usr/share/iserv/modules/sportoase
```

### "Composer not found"
```bash
# Composer installieren
apt install composer
```

### Modul wird nicht angezeigt
```bash
# Apache neu starten
systemctl restart apache2
```

---

## 📖 Weiterführende Dokumentation

- **Vollständige Anleitung**: Siehe [INSTALLATION.md](INSTALLATION.md)
- **Feature-Übersicht**: Siehe [README.md](README.md)
- **Support**: sportoase.kg@gmail.com

---

## ⚙️ Konfiguration nach Installation

### Zeitperioden anpassen:
Bearbeiten Sie die Datei:
```
/usr/share/iserv/iservchk/modules/SportOase/src/Service/BookingService.php
```

### Admin-Rechte vergeben:
Admin-Rechte werden über die IServ-Benutzerverwaltung vergeben. Fügen Sie Benutzer zur "SportOase-Admin" Gruppe hinzu.

---

**Viel Erfolg mit SportOase! 🎯⚽**
