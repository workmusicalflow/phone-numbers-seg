#!/bin/bash

# Script de test pour l'API locale WhatsApp
# Ce script permet de tester l'API WhatsApp locale qui communique avec l'API Cloud de Meta

# Configuration
LOG_FILE="./logs/local-whatsapp-api-test.log"
BASE_URL="http://localhost:8000"  # URL du serveur local
TEMPLATES_ENDPOINT="/api/whatsapp/templates/approved.php"
GRAPHQL_ENDPOINT="/graphql.php"
TEST_PHONE_NUMBER="+22556789012"  # Numéro de test (à adapter selon vos besoins)

# Créer le répertoire des logs s'il n'existe pas
mkdir -p ./logs

# Fonction de journalisation
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Démarrer un nouveau fichier de log
echo "--- Test API WhatsApp locale démarré $(date '+%Y-%m-%d %H:%M:%S') ---" > $LOG_FILE

log_message "Configuration des tests avec:"
log_message "  - URL de base: $BASE_URL"
log_message "  - Endpoint templates: $TEMPLATES_ENDPOINT"
log_message "  - Endpoint GraphQL: $GRAPHQL_ENDPOINT"
log_message "  - Numéro de test: $TEST_PHONE_NUMBER"
log_message "\n"

# Test 1: Vérifier la santé de l'API locale
log_message "Test 1: Vérification de la santé de l'API locale"
curl -s -X GET "$BASE_URL/api.php?health=check" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie-jar cookies.txt \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 2: Récupérer les templates approuvés
log_message "Test 2: Récupération des templates approuvés"
curl -s -X GET "$BASE_URL$TEMPLATES_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 3: Récupérer les templates avec forceRefresh=true
log_message "Test 3: Récupération des templates avec forceRefresh=true"
curl -s -X GET "$BASE_URL$TEMPLATES_ENDPOINT?force_refresh=true" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 4: Requête GraphQL pour les templates utilisateurs
log_message "Test 4: Requête GraphQL pour les templates utilisateurs"
curl -s -X POST "$BASE_URL$GRAPHQL_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d '{"query": "query { getWhatsAppUserTemplates { id name language status } }"}' \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 5: Requête GraphQL pour les métriques d'utilisation des templates
log_message "Test 5: Requête GraphQL pour les métriques d'utilisation des templates"
curl -s -X POST "$BASE_URL$GRAPHQL_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d '{"query": "query { getWhatsAppTemplateUsageMetrics(startDate: \"2025-01-01\", endDate: \"2025-05-21\") { totalUsage uniqueTemplates error } }"}' \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 6: Requête GraphQL pour récupérer les templates les plus utilisés
log_message "Test 6: Requête GraphQL pour les templates les plus utilisés"
curl -s -X POST "$BASE_URL$GRAPHQL_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d '{"query": "query { getMostUsedWhatsAppTemplates(limit: 5) { templateName count language } }"}' \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 7: Tester l'envoi d'un message template (simulation)
log_message "Test 7: Simulation d'envoi de message template via GraphQL"
curl -s -X POST "$BASE_URL$GRAPHQL_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d "{\"query\": \"mutation { sendWhatsAppMessage(message: { phoneNumber: \\\"$TEST_PHONE_NUMBER\\\", type: \\\"template\\\", templateName: \\\"greeting\\\", templateLanguage: \\\"fr\\\" }) { id status timestamp } }\"}" \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

log_message "Tests API WhatsApp locale terminés"