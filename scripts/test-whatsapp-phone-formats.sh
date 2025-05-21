#!/bin/bash

# Script pour tester différents formats de numéros de téléphone avec l'API WhatsApp
# Ce script exécute le test PHP et capture les résultats dans un fichier log

echo "=== Test des formats de numéros WhatsApp ==="
echo "Date du test: $(date)"

# Créer le dossier de logs si nécessaire
LOG_DIR="/Users/ns2poportable/Desktop/phone-numbers-seg/logs"
mkdir -p "$LOG_DIR"

# Nom du fichier log avec timestamp
LOG_FILE="$LOG_DIR/whatsapp-number-test-$(date +%Y%m%d-%H%M%S).log"

echo "Les résultats seront enregistrés dans $LOG_FILE"

# Exécuter le test PHP et capturer la sortie
php /Users/ns2poportable/Desktop/phone-numbers-seg/scripts/test-whatsapp-phone-formats.php | tee "$LOG_FILE"

# Analyser les résultats
echo ""
echo "=== Récapitulatif du test ==="
echo "Formats qui ont réussi:"
grep -A 1 "Message envoyé avec succès" "$LOG_FILE" | grep "Numéro" | sort | uniq

echo ""
echo "Formats qui ont échoué:"
grep -A 1 "Erreur API" "$LOG_FILE" | grep "Numéro" | sort | uniq

echo ""
echo "Format recommandé (basé sur les conclusions du test):"
grep -A 3 "Format(s) recommandé(s)" "$LOG_FILE"

echo ""
echo "Test terminé. Consultez $LOG_FILE pour les détails complets."