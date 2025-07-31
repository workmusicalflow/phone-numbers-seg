#!/bin/bash

# Script de test pour l'envoi d'un template WhatsApp avec image d'en-tête
# Ce script teste l'envoi du template qshe_invitation1 qui nécessite un en-tête image

# Configuration
LOG_FILE="./logs/image-template-test.log"
META_API_URL="https://graph.facebook.com/v22.0"
PHONE_NUMBER_ID="660953787095211"
WABA_ID="664409593123173"
ACCESS_TOKEN="EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K"

# Image example URL trouvée dans le résultat précédent pour qshe_invitation1
IMAGE_URL="https://scontent.whatsapp.net/v/t61.29466-34/490583588_1194607085081673_2117468919397060704_n.jpg?ccb=1-7&_nc_sid=8b1bef&_nc_ohc=4vWztmG3EtMQ7kNvwEuOaKo&_nc_oc=AdlqBByix8PnxSYWb2AtNz4YEqq4XMj5W4ddzztSarzBEi3ncoyXiRoJKduQgHwI_4AF1x1HFkhGDLP1BV8mslAw&_nc_zt=3&_nc_ht=scontent.whatsapp.net&edm=AH51TzQEAAAA&_nc_gid=E7m-XW6fMrnndGsdO1eVzg&oh=01_Q5Aa1gE85niFGyKCQn125W5RMXGifmULwZMP_Vt4qKOZEcYOOQ&oe=68550AB2"

# Numéro de téléphone de test réel
TEST_PHONE_NUMBER="+2250777104936"  # Numéro réel pour test d'envoi

# Créer le répertoire des logs s'il n'existe pas
mkdir -p ./logs

# Fonction de journalisation
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Démarrer un nouveau fichier de log
echo "--- Test d'envoi de Template avec Image démarré $(date '+%Y-%m-%d %H:%M:%S') ---" > $LOG_FILE

log_message "Configuration:"
log_message "  - API URL: $META_API_URL"
log_message "  - PHONE_NUMBER_ID: $PHONE_NUMBER_ID"
log_message "  - Numéro de test: $TEST_PHONE_NUMBER"
log_message "  - URL de l'image: $IMAGE_URL"
log_message "\n"

# Test 1: Tester l'upload d'une image
log_message "Test 1: Upload d'image pour utilisation dans le template"
curl -s -X POST "$META_API_URL/$PHONE_NUMBER_ID/media" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "file_url": "'$IMAGE_URL'"
    }' \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Extrait l'ID de l'image depuis le log (en supposant que l'upload réussit)
MEDIA_ID=$(grep -o '"id":[[:space:]]*"[^"]*"' $LOG_FILE | head -1 | awk -F'"' '{print $4}')

if [ -z "$MEDIA_ID" ]; then
    log_message "Aucun ID média trouvé, on utilise directement l'URL pour le test suivant"
    MEDIA_METHOD="link"
    MEDIA_VALUE="$IMAGE_URL"
else
    log_message "ID média obtenu: $MEDIA_ID"
    MEDIA_METHOD="id"
    MEDIA_VALUE="$MEDIA_ID"
fi

# Test 2: Tester l'envoi du template avec l'image en mode DEBUG
log_message "Test 2: Envoi du template 'qshe_invitation1' avec en-tête image (mode DEBUG)"
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
            },
            "components": [
                {
                    "type": "header",
                    "parameters": [
                        {
                            "type": "image",
                            "image": {
                                "'$MEDIA_METHOD'": "'$MEDIA_VALUE'"
                            }
                        }
                    ]
                }
            ]
        }
    }' \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

# Test 3: Tester l'envoi du template avec les paramètres de bouton (quick reply "Se désabonner")
log_message "Test 3: Envoi du template avec les paramètres de bouton (mode DEBUG)"
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
            },
            "components": [
                {
                    "type": "header",
                    "parameters": [
                        {
                            "type": "image",
                            "image": {
                                "'$MEDIA_METHOD'": "'$MEDIA_VALUE'"
                            }
                        }
                    ]
                },
                {
                    "type": "button",
                    "sub_type": "quick_reply",
                    "index": "0",
                    "parameters": [
                        {
                            "type": "payload",
                            "payload": "UNSUBSCRIBE"
                        }
                    ]
                }
            ]
        }
    }' \
    | tee -a $LOG_FILE | jq -r '.' 2>/dev/null || tee -a $LOG_FILE

log_message "\n"

log_message "Tests d'envoi de template avec image terminés"