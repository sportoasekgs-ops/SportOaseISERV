#!/bin/bash
#
# SportOase - Automatisches Installations-Skript für IServ
# Verwendung: sudo bash install.sh
#

set -e

echo "================================================"
echo "  SportOase IServ-Modul Installation"
echo "================================================"
echo ""

# Farben für bessere Lesbarkeit
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Prüfen ob als Root ausgeführt
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Bitte als Root ausführen: sudo bash install.sh${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Root-Rechte vorhanden${NC}"
echo ""

# Schritt 1: Prüfen ob IServ-Server
echo "🔍 Prüfe IServ-Installation..."
if [ ! -d "/usr/share/iserv" ]; then
    echo -e "${RED}❌ Kein IServ-Server erkannt!${NC}"
    echo "Dieses Skript funktioniert nur auf IServ-Servern."
    exit 1
fi
echo -e "${GREEN}✅ IServ-Server erkannt${NC}"
echo ""

# Schritt 2: Composer-Abhängigkeiten installieren
echo "📦 Installiere PHP-Abhängigkeiten..."
if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ composer.json nicht gefunden!${NC}"
    echo "Bitte führen Sie das Skript im SportOase-Verzeichnis aus."
    exit 1
fi

composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ PHP-Abhängigkeiten installiert${NC}"
echo ""

# Schritt 3: Assets bauen
echo "🎨 Baue Frontend-Assets..."
if [ -f "package.json" ]; then
    npm install --silent
    npm run build --silent
    echo -e "${GREEN}✅ Assets gebaut${NC}"
else
    echo -e "${YELLOW}⚠️  package.json nicht gefunden - überspringe Asset-Build${NC}"
fi
echo ""

# Schritt 4: Datenbank-Migrationen
echo "🗄️  Führe Datenbank-Migrationen aus..."
if [ -f "bin/console" ]; then
    sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction
    echo -e "${GREEN}✅ Migrationen erfolgreich${NC}"
else
    echo -e "${RED}❌ bin/console nicht gefunden!${NC}"
    exit 1
fi
echo ""

# Schritt 5: Berechtigungen setzen
echo "🔐 Setze Dateiberechtigungen..."
chown -R www-data:www-data .
chmod -R 755 .
echo -e "${GREEN}✅ Berechtigungen gesetzt${NC}"
echo ""

# Schritt 6: Umgebungsvariablen prüfen
echo "⚙️  Prüfe Konfiguration..."
ENV_FILE="/etc/iserv/sportoase.env"

if [ ! -f "$ENV_FILE" ]; then
    echo -e "${YELLOW}⚠️  Umgebungsvariablen-Datei nicht gefunden${NC}"
    echo ""
    echo "Erstelle Beispiel-Konfiguration: $ENV_FILE"
    
    cat > "$ENV_FILE" << 'EOF'
# IServ OAuth2 Einstellungen (ANPASSEN!)
ISERV_BASE_URL=https://ihr-iserv.de
ISERV_CLIENT_ID=sportoase
ISERV_CLIENT_SECRET=HIER-GEHEIMEN-SCHLÜSSEL-EINTRAGEN

# E-Mail Einstellungen (optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=sportoase.kg@gmail.com
SMTP_PASS=HIER-EMAIL-PASSWORT-EINTRAGEN
ADMIN_EMAIL=sportoase.kg@gmail.com
ENABLE_NOTIFICATIONS=true

# Buchungseinstellungen
MAX_STUDENTS_PER_PERIOD=5
BOOKING_ADVANCE_MINUTES=60

# Google Calendar (optional - leer lassen wenn nicht benötigt)
GOOGLE_CALENDAR_ID=
GOOGLE_SERVICE_ACCOUNT_JSON=
EOF

    chmod 600 "$ENV_FILE"
    echo -e "${YELLOW}📝 Bitte bearbeiten Sie: $ENV_FILE${NC}"
    echo -e "${YELLOW}   und passen Sie die Werte an!${NC}"
else
    echo -e "${GREEN}✅ Konfigurationsdatei vorhanden: $ENV_FILE${NC}"
fi
echo ""

# Schritt 7: Apache neu starten
echo "🔄 Starte Webserver neu..."
systemctl restart apache2
echo -e "${GREEN}✅ Webserver neu gestartet${NC}"
echo ""

# Zusammenfassung
echo "================================================"
echo -e "${GREEN}🎉 Installation erfolgreich abgeschlossen!${NC}"
echo "================================================"
echo ""
echo "📍 Nächste Schritte:"
echo ""
echo "1. Umgebungsvariablen anpassen:"
echo "   nano $ENV_FILE"
echo ""
echo "2. Modul im IServ aktivieren:"
echo "   IServ Admin → System → Module → SportOase"
echo ""
echo "3. SportOase aufrufen:"
echo "   https://ihr-iserv.de/sportoase"
echo ""
echo "📖 Dokumentation:"
echo "   - SCHNELLSTART.md (Diese Datei!)"
echo "   - INSTALLATION.md (Ausführlich)"
echo "   - README.md (Feature-Übersicht)"
echo ""
echo "🆘 Support: sportoase.kg@gmail.com"
echo ""
