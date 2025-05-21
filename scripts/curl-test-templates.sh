#!/bin/bash

# Script de test pour l'API WhatsApp Templates avec cURL

# Configuration
BASE_URL="http://localhost:8000"
ENDPOINT="/api/whatsapp/templates/approved.php"
LOG_FILE="./logs/curl-test.log"

# Créer le répertoire des logs s'il n'existe pas
mkdir -p ./logs

# Fonction de journalisation
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Démarrer un nouveau fichier de log
echo "--- Test cURL démarré $(date '+%Y-%m-%d %H:%M:%S') ---" > $LOG_FILE

# Test 1: Requête de base pour les templates approuvés
log_message "Test 1: Requête de base pour les templates"
curl -v "$BASE_URL$ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie-jar cookies.txt \
     2>&1 | tee -a $LOG_FILE

log_message "\n"

# Test 2: Requête avec statut spécifique
log_message "Test 2: Requête avec statut=APPROVED"
curl -v "$BASE_URL$ENDPOINT?status=APPROVED" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     2>&1 | tee -a $LOG_FILE

log_message "\n"

# Test 3: Requête avec forceRefresh=true
log_message "Test 3: Requête avec forceRefresh=true"
curl -v "$BASE_URL$ENDPOINT?force_refresh=true" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     2>&1 | tee -a $LOG_FILE

log_message "\n"

# Test 4: Accès direct au contrôleur WhatsApp
log_message "Test 4: Accès direct au contrôleur WhatsApp"
curl -v -X POST "$BASE_URL/graphql.php" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d '{"query": "query { getWhatsAppTemplateUsageMetrics(startDate: \"2025-01-01\", endDate: \"2025-05-21\") { totalUsage uniqueTemplates error } }"}' \
     2>&1 | tee -a $LOG_FILE

log_message "\n"

log_message "Tests terminés"