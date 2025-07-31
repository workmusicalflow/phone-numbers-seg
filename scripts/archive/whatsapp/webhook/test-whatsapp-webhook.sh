#!/bin/bash

# Script pour tester le webhook WhatsApp

# Test de vérification du webhook (GET)
echo "Test de vérification du webhook (GET)..."
curl -v "http://localhost:8000/whatsapp/webhook.php?hub_mode=subscribe&hub_verify_token=oracle_whatsapp_webhook_verification_token&hub_challenge=123456789"
echo -e "\n\n"

# Test de réception d'un message (POST)
echo "Test de réception d'un message (POST)..."
curl -v -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "object": "whatsapp_business_account",
    "entry": [
      {
        "id": "664409593123173",
        "changes": [
          {
            "value": {
              "messaging_product": "whatsapp",
              "metadata": {
                "display_phone_number": "15129854992",
                "phone_number_id": "660953787095211"
              },
              "contacts": [
                {
                  "profile": {
                    "name": "Test User"
                  },
                  "wa_id": "22507089037"
                }
              ],
              "messages": [
                {
                  "from": "22507089037",
                  "id": "wamid.HBgLMjI1MDcwODkwMzcVAgASGBQzRUJGNkFDOTJBMkZCQkVDMTcyRQA=",
                  "timestamp": "1620000000",
                  "text": {
                    "body": "Ceci est un message de test"
                  },
                  "type": "text"
                }
              ]
            },
            "field": "messages"
          }
        ]
      }
    ]
  }' \
  "http://localhost:8000/whatsapp/webhook.php"
echo -e "\n\n"

echo "Tests terminés."