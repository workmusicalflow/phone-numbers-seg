#!/bin/bash

# Script de test pour l'API Cloud de Meta (WhatsApp Business API)
# Ce script permet de tester directement la communication avec l'API Meta

# Configuration
LOG_FILE="./logs/meta-api-test.log"
META_API_URL="https://graph.facebook.com/v22.0" # Utilisation de la v22.0 comme indiqué dans .env.whatsapp.example
PHONE_NUMBER_ID="660953787095211"               # ID de numéro WhatsApp depuis .env.whatsapp.example
ACCESS_TOKEN="EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K" # Token d'accès depuis .env.whatsapp.example
WABA_ID="664409593123173"                       # ID du Business Account depuis .env.whatsapp.example
APP_ID="1193922949108494"                       # ID de l'application depuis .env.whatsapp.example

# Créer le répertoire des logs s'il n'existe pas
mkdir -p ./logs

# Fonction de journalisation
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Démarrer un nouveau fichier de log
echo "--- Test API Meta démarré $(date '+%Y-%m-%d %H:%M:%S') ---" > $LOG_FILE

# Vérifions si nous avons les variables d'environnement pour remplacer les valeurs
if [ -n "$META_PHONE_NUMBER_ID" ]; then
    PHONE_NUMBER_ID=$META_PHONE_NUMBER_ID
    log_message "Utilisation de l'ID de téléphone WhatsApp depuis les variables d'environnement"
fi

if [ -n "$META_ACCESS_TOKEN" ]; then
    ACCESS_TOKEN=$META_ACCESS_TOKEN
    log_message "Utilisation du token d'accès depuis les variables d'environnement"
fi

# Vérification des credentials (déjà configurés dans le script)

# Test 1: Vérifier les templates disponibles
log_message "Test 1: Récupération des templates WhatsApp via l'API Meta"
curl -s -X GET "$META_API_URL/$PHONE_NUMBER_ID/message_templates" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 2: Filtrer les templates par statut (APPROVED)
log_message "Test 2: Récupération des templates APPROVED uniquement"
curl -s -X GET "$META_API_URL/$PHONE_NUMBER_ID/message_templates?status=APPROVED" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 3: Vérifier un template spécifique
log_message "Test 3: Récupération des détails d'un template spécifique (greeting)"
curl -s -X GET "$META_API_URL/$PHONE_NUMBER_ID/message_templates?name=greeting" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 4: Récupérer les métriques 
log_message "Test 4: Récupération des métriques d'utilisation de WhatsApp"
curl -s -X GET "$META_API_URL/$PHONE_NUMBER_ID/insights?metric=sent,delivered" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 5: Vérifier l'état du Business Account
log_message "Test 5: Vérification de l'état du Business Account"
curl -s -X GET "$META_API_URL/$WABA_ID?fields=id,name,message_template_namespace,timezone_id,analytics_access_token" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 6: Informations de base sur le compte 
log_message "Test 6: Informations de base sur le compte"
curl -s -X GET "$META_API_URL/me?fields=id,name,verification_status,messaging_enabled" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 7: Informations sur les médias disponibles
log_message "Test 7: Informations sur les médias disponibles"
curl -s -X GET "$META_API_URL/$PHONE_NUMBER_ID/media" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 8: Vérifier la limite des APILimits et le statut du compte
log_message "Test 8: Vérification des limites d'API et du statut du compte"
curl -s -X GET "$META_API_URL/$PHONE_NUMBER_ID?fields=qualified_for_commerce,quality_rating,verified_name,code_verification_status" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

log_message "Tests API Meta terminés"