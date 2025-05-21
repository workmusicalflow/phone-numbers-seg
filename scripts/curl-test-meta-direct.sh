#!/bin/bash

# Script de test direct pour l'API Cloud de Meta (WhatsApp Business API)
# Ce script teste la communication directe avec l'API Meta en utilisant la structure correcte des endpoints

# Configuration d'après la documentation
LOG_FILE="./logs/meta-direct-test.log"
META_API_URL="https://graph.facebook.com/v22.0"
PHONE_NUMBER_ID="660953787095211"
WABA_ID="664409593123173"
APP_ID="1193922949108494"
ACCESS_TOKEN="EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K"
TEST_PHONE_NUMBER="+33756781234" # Exemple, à remplacer par un numéro de test réel

# Créer le répertoire des logs s'il n'existe pas
mkdir -p ./logs

# Fonction de journalisation
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Démarrer un nouveau fichier de log
echo "--- Test Direct API Meta démarré $(date '+%Y-%m-%d %H:%M:%S') ---" > $LOG_FILE

log_message "Configuration des tests:"
log_message "  - API URL: $META_API_URL"
log_message "  - PHONE_NUMBER_ID: $PHONE_NUMBER_ID"
log_message "  - WABA_ID: $WABA_ID"
log_message "  - APP_ID: $APP_ID"
log_message "\n"

# Test 1: Récupérer les informations du compte Business WhatsApp
log_message "Test 1: Récupération des informations du compte WABA"
curl -s -X GET "$META_API_URL/$WABA_ID?fields=id,name,currency" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 2: Vérifier les numéros de téléphone associés au WABA 
log_message "Test 2: Récupération des numéros associés au WABA"
curl -s -X GET "$META_API_URL/$WABA_ID/phone_numbers" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 3: Récupérer les templates via le WABA ID (structure correcte)
log_message "Test 3: Récupération des templates WhatsApp via le WABA ID"
curl -s -X GET "$META_API_URL/$WABA_ID/message_templates" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 4: Récupérer les templates via le Phone Number ID (test de la structure précédente qui avait échoué)
log_message "Test 4: Récupération des templates via le PHONE_NUMBER_ID (structure incorrecte)"
curl -s -X GET "$META_API_URL/$PHONE_NUMBER_ID/message_templates" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 5: Vérifier la validité du jeton d'accès
log_message "Test 5: Vérification du jeton d'accès"
curl -s -X GET "https://graph.facebook.com/debug_token?input_token=$ACCESS_TOKEN&access_token=$ACCESS_TOKEN" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 6: Récupérer les métriques d'utilisation (via le WABA ID)
log_message "Test 6: Récupération des métriques d'utilisation via le WABA ID"
curl -s -X GET "$META_API_URL/$WABA_ID/insights?metric=sent_message" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 7: Récupérer les limites de messagerie (via le WABA ID)
log_message "Test 7: Récupération des limites de messagerie"
curl -s -X GET "$META_API_URL/$WABA_ID/messaging_limits" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 8: Récupérer les informations de qualité du numéro
log_message "Test 8: Récupération des informations de qualité du numéro"
curl -s -X GET "$META_API_URL/$PHONE_NUMBER_ID?fields=quality_rating,verified_name" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 9: Tester l'envoi d'un message template (sans envoyer réellement)
# On utilise ?debug=all pour voir la structure sans envoyer réellement
log_message "Test 9: Test d'envoi d'un message template (mode debug)"
curl -s -X POST "$META_API_URL/$PHONE_NUMBER_ID/messages?debug=all" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "'$TEST_PHONE_NUMBER'",
        "type": "template",
        "template": {
            "name": "hello_world",
            "language": {
                "code": "fr"
            },
            "components": [
                {
                    "type": "body",
                    "parameters": [
                        {
                            "type": "text",
                            "text": "Monde"
                        }
                    ]
                }
            ]
        }
    }' \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

log_message "Tests API Meta directs terminés"