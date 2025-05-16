#!/bin/bash

# Script pour organiser les scripts WhatsApp en dossiers logiques

echo "Organisation des scripts WhatsApp..."
echo "==================================="

# Créer les dossiers d'organisation
mkdir -p scripts/archive/whatsapp/webhook
mkdir -p scripts/archive/whatsapp/localtunnel
mkdir -p scripts/archive/whatsapp/tests
mkdir -p scripts/archive/whatsapp/config
mkdir -p scripts/active/whatsapp

# Déplacer les scripts de démarrage utiles dans un dossier actif
mv scripts/start-webhook-server.sh scripts/active/whatsapp/ 2>/dev/null
mv scripts/start-webhook-tunnel.sh scripts/active/whatsapp/ 2>/dev/null  
mv scripts/start-whatsapp-integration.sh scripts/active/whatsapp/ 2>/dev/null

# Déplacer les scripts de test utiles
mv scripts/test-whatsapp-graphql.php scripts/active/whatsapp/ 2>/dev/null
mv scripts/test-whatsapp-entities-persistence.php scripts/active/whatsapp/ 2>/dev/null
mv scripts/validate-whatsapp-entities.php scripts/active/whatsapp/ 2>/dev/null

# Archiver les scripts webhook de test
mv scripts/test-whatsapp-webhook.sh scripts/archive/whatsapp/webhook/ 2>/dev/null
mv scripts/test_whatsapp_integration.php scripts/archive/whatsapp/tests/ 2>/dev/null

# Archiver les scripts de configuration  
mv scripts/check-whatsapp-config.php scripts/archive/whatsapp/config/ 2>/dev/null

echo ""
echo "Organisation terminée !"
echo ""
echo "Scripts actifs dans: scripts/active/whatsapp/"
ls -la scripts/active/whatsapp/ 2>/dev/null | grep -v "^total" | grep -v "^d" | awk '{print "  - " $9}'

echo ""
echo "Scripts archivés dans: scripts/archive/whatsapp/"
find scripts/archive/whatsapp -type f -name "*.sh" -o -name "*.php" | sed 's/scripts\/archive\/whatsapp\//  - /'