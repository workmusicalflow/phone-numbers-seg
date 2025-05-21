#!/bin/bash

# Script de test pour l'envoi d'un template WhatsApp qui existe dans le compte
# Ce script teste l'envoi de messages template WhatsApp avec l'API Meta

# Configuration
LOG_FILE="./logs/template-send-test.log"
META_API_URL="https://graph.facebook.com/v22.0"
PHONE_NUMBER_ID="660953787095211"
WABA_ID="664409593123173"
ACCESS_TOKEN="EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K"

# Numéro de téléphone de test VALID
TEST_PHONE_NUMBER="+12345678900"  # Remplacer par un vrai numéro pour un test réel

# Créer le répertoire des logs s'il n'existe pas
mkdir -p ./logs

# Fonction de journalisation
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Démarrer un nouveau fichier de log
echo "--- Test d'envoi de Template WhatsApp démarré $(date '+%Y-%m-%d %H:%M:%S') ---" > $LOG_FILE

log_message "Configuration:"
log_message "  - API URL: $META_API_URL"
log_message "  - PHONE_NUMBER_ID: $PHONE_NUMBER_ID"
log_message "  - Numéro de test: $TEST_PHONE_NUMBER"
log_message "\n"

# Test 1: Tester l'envoi du template "connection_check" en mode DEBUG (pas d'envoi réel)
log_message "Test 1: Test d'envoi du template 'connection_check' (mode DEBUG, pas d'envoi réel)"
curl -s -X POST "$META_API_URL/$PHONE_NUMBER_ID/messages?debug=all" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "'$TEST_PHONE_NUMBER'",
        "type": "template",
        "template": {
            "name": "connection_check",
            "language": {
                "code": "fr"
            }
        }
    }' \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 2: Simuler l'envoi du template "qshe_invitation1" en mode DEBUG
log_message "Test 2: Test d'envoi du template 'qshe_invitation1' (mode DEBUG, pas d'envoi réel)"
curl -s -X POST "$META_API_URL/$PHONE_NUMBER_ID/messages?debug=all" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "'$TEST_PHONE_NUMBER'",
        "type": "template",
        "template": {
            "name": "qshe_invitation1",
            "language": {
                "code": "fr"
            }
        }
    }' \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 3: Tester l'envoi d'un message texte (nécessite conversation ouverte, probablement échouera en raison de la fenêtre de 24h)
log_message "Test 3: Test d'envoi de message texte (probablement échec à cause de la fenêtre de 24h)"
curl -s -X POST "$META_API_URL/$PHONE_NUMBER_ID/messages?debug=all" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "'$TEST_PHONE_NUMBER'",
        "type": "text",
        "text": {
            "body": "Ceci est un message de test"
        }
    }' \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

log_message "Tests d'envoi de template terminés"