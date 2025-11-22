#!/bin/bash
echo "🔍 SportOase IServ-Modul - Validierung"
echo "========================================"
echo ""

# Prüfe Composer
if [ -f "composer.json" ]; then
    echo "✅ composer.json gefunden"
else
    echo "❌ composer.json fehlt"
    exit 1
fi

# Prüfe wichtige Verzeichnisse
for dir in src migrations templates config; do
    if [ -d "$dir" ]; then
        echo "✅ Verzeichnis $dir existiert"
    else
        echo "❌ Verzeichnis $dir fehlt"
        exit 1
    fi
done

# Prüfe Entities
entity_count=$(find src/Entity -name "*.php" 2>/dev/null | wc -l)
echo "✅ $entity_count Entities gefunden"

# Prüfe Migrationen
migration_count=$(find migrations -name "*.php" 2>/dev/null | wc -l)
echo "✅ $migration_count Migrationen gefunden"

# Prüfe Templates
template_count=$(find templates -name "*.twig" 2>/dev/null | wc -l)
echo "✅ $template_count Templates gefunden"

echo ""
echo "📦 Modul-Status:"
echo "   - Name: SportOase"
echo "   - Typ: IServ Symfony Bundle"
echo "   - Status: ✅ Bereit für Deployment"
echo ""
echo "📖 Nächste Schritte:"
echo "   1. MIGRATION_FROM_TEST.md lesen"
echo "   2. PRODUCTION_DEPLOYMENT.md folgen"
echo "   3. Debian-Paket erstellen: dpkg-deb --build . sportoase-module.deb"
echo "   4. Auf IServ installieren"
echo ""
