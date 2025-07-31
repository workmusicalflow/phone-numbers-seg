#!/bin/bash

# Script de test pour l'envoi de messages WhatsApp
# Ce script teste l'envoi de messages WhatsApp via l'API REST et GraphQL

# Configuration
LOG_FILE="./logs/whatsapp-send-test.log"
BASE_URL="http://localhost:8000"
REST_ENDPOINT="/api/whatsapp/send.php"
GRAPHQL_ENDPOINT="/graphql.php"
TEST_PHONE_NUMBER="+22556789012"  # Numéro de test

# Créer le répertoire des logs s'il n'existe pas
mkdir -p ./logs

# Fonction de journalisation
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Démarrer un nouveau fichier de log
echo "--- Test d'envoi de messages WhatsApp démarré $(date '+%Y-%m-%d %H:%M:%S') ---" > $LOG_FILE

log_message "Configuration des tests avec:"
log_message "  - URL de base: $BASE_URL"
log_message "  - Endpoint REST: $REST_ENDPOINT"
log_message "  - Endpoint GraphQL: $GRAPHQL_ENDPOINT"
log_message "  - Numéro de test: $TEST_PHONE_NUMBER"
log_message "\n"

# Créer une session pour les tests
log_message "Création de session pour les tests (cookie)"
curl -s -v -X GET "$BASE_URL/login.php?test=1" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie-jar cookies.txt \
     2>&1 | tee -a $LOG_FILE

log_message "\n"

# Test 1: Envoi d'un message texte via REST
log_message "Test 1: Envoi d'un message texte via REST"
curl -s -X POST "$BASE_URL$REST_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d "{\"recipient\": \"$TEST_PHONE_NUMBER\", \"type\": \"text\", \"content\": \"Test message from API\"}" \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 2: Envoi d'un message template via REST
log_message "Test 2: Envoi d'un message template via REST"
curl -s -X POST "$BASE_URL$REST_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d "{\"recipient\": \"$TEST_PHONE_NUMBER\", \"type\": \"template\", \"templateName\": \"greeting\", \"languageCode\": \"fr\", \"components\": [{\"type\": \"body\", \"parameters\": [{\"type\": \"text\", \"text\": \"Utilisateur\"}]}]}" \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 3: Envoi d'un message texte via GraphQL
log_message "Test 3: Envoi d'un message texte via GraphQL"
curl -s -X POST "$BASE_URL$GRAPHQL_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d "{\"query\": \"mutation { sendWhatsAppMessage(message: { recipient: \\\"$TEST_PHONE_NUMBER\\\", type: \\\"text\\\", content: \\\"Test message from GraphQL\\\" }) { id status timestamp } }\"}" \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 4: Envoi d'un message template via GraphQL avec la structure correcte
log_message "Test 4: Envoi d'un message template via GraphQL (structure correcte)"
curl -s -X POST "$BASE_URL$GRAPHQL_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d "{\"query\": \"mutation { sendWhatsAppMessage(message: { recipient: \\\"$TEST_PHONE_NUMBER\\\", type: \\\"template\\\", templateName: \\\"greeting\\\", languageCode: \\\"fr\\\", components: [{\\\"type\\\": \\\"body\\\", \\\"parameters\\\": [{\\\"type\\\": \\\"text\\\", \\\"text\\\": \\\"Utilisateur\\\"}]}] }) { id status timestamp } }\"}" \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 5: Vérification du statut d'un message via GraphQL
log_message "Test 5: Vérification du statut d'un message via GraphQL"
MOCK_MESSAGE_ID="mock123456789" # Remplacer par un ID réel si disponible
curl -s -X POST "$BASE_URL$GRAPHQL_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d "{\"query\": \"query { getWhatsAppMessageStatus(messageId: \\\"$MOCK_MESSAGE_ID\\\") { status timestamp } }\"}" \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 6: Récupération de l'historique des messages via GraphQL
log_message "Test 6: Récupération de l'historique des messages via GraphQL"
curl -s -X POST "$BASE_URL$GRAPHQL_ENDPOINT" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     --cookie cookies.txt \
     -d "{\"query\": \"query { getWhatsAppMessages(limit: 5) { messages { id phoneNumber direction type content status timestamp } totalCount hasMore } }\"}" \
     | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

log_message "Tests d'envoi de messages WhatsApp terminés"