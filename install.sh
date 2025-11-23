#!/bin/bash
#
# SportOase - Post-Installation-Skript für IServ
# Verwendung: Dieses Skript wird NACH der Paket-Installation ausgeführt
# sudo bash install.sh
#

set -e

echo "================================================"
echo "  SportOase IServ-Modul - Post-Installation"
echo "================================================"
echo ""

# Farben
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Prüfen ob als Root
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
    exit 1
fi
echo -e "${GREEN}✅ IServ-Server erkannt${NC}"
echo ""

# Schritt 2: Prüfen ob Modul installiert
BUNDLE_DIR="/usr/share/iserv/iservchk/modules/SportOase"
if [ ! -d "$BUNDLE_DIR" ]; then
    echo -e "${RED}❌ SportOase-Modul nicht gefunden in $BUNDLE_DIR${NC}"
    echo "Bitte installieren Sie zuerst das Debian-Paket:"
    echo "  apt install iserv-sportoase"
    exit 1
fi
echo -e "${GREEN}✅ SportOase-Modul gefunden${NC}"
echo ""

# Schritt 3: Datenbank-Migrationen
echo "🗄️  Führe Datenbank-Migrationen aus..."

if [ -d "$BUNDLE_DIR/migrations" ]; then
    # Verwende IServ's eigenes Console
    cd "$BUNDLE_DIR/migrations"
    sudo -u www-data php /usr/share/iserv/www/iserv console doctrine:migrations:migrate \
        --configuration=doctrine.yaml \
        --no-interaction 2>&1
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Migrationen erfolgreich${NC}"
    else
        echo -e "${YELLOW}⚠️  Migrationen möglicherweise bereits ausgeführt${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Keine Migrationen gefunden${NC}"
fi
echo ""

# Schritt 4: Umgebungsvariablen prüfen
echo "⚙️  Prüfe Konfiguration..."
ENV_FILE="/etc/iserv/sportoase.env"

if [ ! -f "$ENV_FILE" ]; then
    echo -e "${YELLOW}⚠️  Umgebungsvariablen-Datei nicht gefunden${NC}"
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

# Google Calendar (optional)
GOOGLE_CALENDAR_ID=
GOOGLE_SERVICE_ACCOUNT_JSON=
EOF

    chmod 600 "$ENV_FILE"
    echo -e "${YELLOW}📝 Bitte bearbeiten Sie: $ENV_FILE${NC}"
else
    echo -e "${GREEN}✅ Konfigurationsdatei vorhanden${NC}"
fi
echo ""

# Schritt 5: Apache neu starten
echo "🔄 Starte Webserver neu..."
systemctl restart apache2
echo -e "${GREEN}✅ Webserver neu gestartet${NC}"
echo ""

# Zusammenfassung
echo "================================================"
echo -e "${GREEN}🎉 Post-Installation erfolgreich!${NC}"
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
echo "   /usr/share/doc/iserv-sportoase/"
echo ""
echo "🆘 Support: sportoase.kg@gmail.com"
echo ""
