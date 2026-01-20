#!/bin/bash
# ============================================
# ENSEMBLE CLEANUP SCRIPT v1.0
# ============================================
# Entfernt alte/überflüssige Dateien
# ⚠️ VOR AUSFÜHRUNG: GIT COMMIT ODER BACKUP!
# ============================================

set -e  # Stop bei Fehlern

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Prüfe ob im richtigen Verzeichnis
if [ ! -f "ensemble.php" ]; then
    echo -e "${RED}FEHLER: ensemble.php nicht gefunden!${NC}"
    echo "Bitte im Plugin-Root-Verzeichnis ausführen."
    exit 1
fi

echo ""
echo "============================================"
echo "  ENSEMBLE CLEANUP SCRIPT"
echo "============================================"
echo ""

# Größe vorher
SIZE_BEFORE=$(du -sh . | cut -f1)
echo "Aktuelle Größe: $SIZE_BEFORE"
echo ""

# ============================================
# PHASE 1: .DS_Store Dateien (Mac)
# ============================================
echo -e "${YELLOW}Phase 1: .DS_Store Dateien entfernen...${NC}"
DS_COUNT=$(find . -name ".DS_Store" | wc -l)
find . -name ".DS_Store" -delete 2>/dev/null || true
echo -e "${GREEN}✓ $DS_COUNT .DS_Store Dateien entfernt${NC}"
echo ""

# ============================================
# PHASE 2: Admin -old Dateien
# ============================================
echo -e "${YELLOW}Phase 2: Admin -old Dateien entfernen...${NC}"
rm -f admin/addons-old.php
rm -f admin/field-builder-old.php
rm -f admin/import-old.php
rm -f admin/taxonomies-old.php
rm -f admin/wizard-old-old.php
echo -e "${GREEN}✓ Admin -old Dateien entfernt (217K)${NC}"
echo ""

# ============================================
# PHASE 3: Includes Backup/Legacy Dateien
# ============================================
echo -e "${YELLOW}Phase 3: Backup-Dateien in includes entfernen...${NC}"
rm -f includes/class-label-system.backup.php
rm -f includes/class-onboarding-handler.backup.php
echo -e "${GREEN}✓ Backup-Dateien entfernt (18K)${NC}"
echo ""

# ============================================
# PHASE 4: CSS Backups
# ============================================
echo -e "${YELLOW}Phase 4: CSS Backup-Dateien entfernen...${NC}"
rm -f assets/css/admin-unified-old.css
rm -f assets/css/shortcodes.backup.css
echo -e "${GREEN}✓ CSS Backups entfernt (150K)${NC}"
echo ""

# ============================================
# PHASE 5: Layout -old Dateien
# ============================================
echo -e "${YELLOW}Phase 5: Layout -old Dateien entfernen...${NC}"
rm -f templates/layouts/lovepop/preset-old.php
rm -f templates/layouts/lovepop/style-old.css
rm -f templates/layouts/club/preset-old.php
rm -f templates/layouts/club/style-old.css
rm -f templates/layouts/kinky/style-old.css
rm -f templates/layouts/simpleclub/preset-old.php
rm -f templates/layouts/simpleclub/style-old.css
rm -f templates/layouts/pure/style-old.css
rm -f templates/layouts/kongress/event-card.backup.php
rm -f templates/layouts/stage/style-old.css
echo -e "${GREEN}✓ Layout -old Dateien entfernt (256K)${NC}"
echo ""

# ============================================
# PHASE 6: Addon -old Dateien
# ============================================
echo -e "${YELLOW}Phase 6: Addon -old Dateien entfernen...${NC}"
rm -f includes/addons/gallery-pro/assets/gallery-pro-old.css
rm -f includes/addons/downloads/assets/css/downloads-old.css
rm -f includes/addons/staff/assets/staff-old.css
rm -f includes/addons/timetable/assets/css/timetable-frontend-old.css
rm -f includes/addons/visual-calendar/templates/settings-old.php
rm -f includes/addons/tickets-pro/templates/admin-page-old.php
rm -f includes/addons/maps/assets/maps-old.css
rm -f includes/addons/catalog/assets/catalog-frontend-old.css
rm -f includes/addons/related-events/assets/related-events-old.css
echo -e "${GREEN}✓ Addon -old Dateien entfernt (160K)${NC}"
echo ""

# ============================================
# OPTIONALE PHASE 7: Legacy Shortcodes (292K!)
# ============================================
echo -e "${YELLOW}Phase 7: Legacy Shortcodes Klasse...${NC}"
if [ -f "includes/class-es-shortcodes-legacy.php" ]; then
    # Prüfe ob irgendwo referenziert
    REFS=$(grep -r "shortcodes-legacy\|Shortcodes_Legacy" --include="*.php" . 2>/dev/null | wc -l || echo "0")
    if [ "$REFS" -eq "0" ]; then
        rm -f includes/class-es-shortcodes-legacy.php
        echo -e "${GREEN}✓ Legacy Shortcodes entfernt (292K)${NC}"
    else
        echo -e "${RED}⚠ Legacy Shortcodes wird noch referenziert ($REFS Stellen) - NICHT gelöscht${NC}"
    fi
else
    echo -e "${GREEN}✓ Bereits entfernt${NC}"
fi
echo ""

# ============================================
# OPTIONALE PHASE 8: Legacy Addon-Ordner
# ============================================
echo -e "${YELLOW}Phase 8: Legacy Addon-Ordner...${NC}"

# Elementor-Pro Addon (ersetzt durch includes/elementor/)
if [ -d "includes/addons/elementor-pro" ]; then
    rm -rf includes/addons/elementor-pro/
    echo -e "${GREEN}✓ elementor-pro Addon entfernt (170K) - ersetzt durch includes/elementor/${NC}"
else
    echo -e "${GREEN}✓ elementor-pro bereits entfernt${NC}"
fi

echo -e "${RED}⚠ MANUELL PRÜFEN vor dem Löschen:${NC}"
echo "  - includes/addons/tickets-legacy/ (169K)"
echo "  - includes/addons/reservations-legacy/ (449K)"
echo ""
echo "Zum Löschen ausführen:"
echo "  rm -rf includes/addons/tickets-legacy/"
echo "  rm -rf includes/addons/reservations-legacy/"
echo ""

# ============================================
# ERGEBNIS
# ============================================
SIZE_AFTER=$(du -sh . | cut -f1)
echo "============================================"
echo -e "${GREEN}CLEANUP ABGESCHLOSSEN!${NC}"
echo "============================================"
echo "Größe vorher:  $SIZE_BEFORE"
echo "Größe nachher: $SIZE_AFTER"
echo ""
echo "Noch zu prüfen (manuell):"
echo "  - Legacy Addon-Ordner tickets-legacy (169K)"
echo "  - Legacy Addon-Ordner reservations-legacy (449K)"
echo "  - Agenda Addon (108K) - registrieren oder entfernen?"
echo ""
echo "Empfehlung: git status && git diff --stat"
