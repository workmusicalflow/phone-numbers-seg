#!/bin/bash

# Script de test pour l'intégration des templates WhatsApp

# Configuration
TEST_PHONE="2250777104936"
TEMPLATE_NAME="hello_world"
TEMPLATE_LANG="en_US"

echo "Test d'envoi de template WhatsApp"
echo "=================================="
echo "Téléphone: $TEST_PHONE"
echo "Template: $TEMPLATE_NAME"
echo "Langue: $TEMPLATE_LANG"
echo

# 1. Test de login
echo "1. Login administrateur..."
LOGIN_RESPONSE=$(curl -s -X POST \
  -H "Content-Type: application/json" \
  -d '{"query":"mutation { login(username: \"admin\", password: \"admin\") }"}' \
  --cookie-jar cookie.txt \
  http://localhost:8000/graphql.php)

if ! echo "$LOGIN_RESPONSE" | grep -q "true"; then
  echo "❌ Échec du login"
  exit 1
fi
echo "✅ Login réussi"
echo

# 2. Test de récupération des templates
echo "2. Récupération des templates WhatsApp..."
TEMPLATES_RESPONSE=$(curl -s -X POST \
  -H "Content-Type: application/json" \
  -d '{"query":"query { getWhatsAppUserTemplates { id template_id name language status } }"}' \
  --cookie cookie.txt \
  http://localhost:8000/graphql.php)

if ! echo "$TEMPLATES_RESPONSE" | grep -q "$TEMPLATE_NAME"; then
  echo "❌ Template $TEMPLATE_NAME non trouvé"
  echo "$TEMPLATES_RESPONSE"
  exit 1
fi
echo "✅ Templates récupérés avec succès"
echo

# 3. Test d'envoi de template
echo "3. Envoi du template WhatsApp..."
SEND_RESPONSE=$(curl -s -X POST \
  -H "Content-Type: application/json" \
  -d "{\"query\":\"mutation SendTemplate(\$input: SendTemplateInput!) { sendWhatsAppTemplateV2(input: \$input) { success messageId error } }\", \"variables\": {\"input\": {\"recipientPhoneNumber\": \"$TEST_PHONE\", \"templateName\": \"$TEMPLATE_NAME\", \"templateLanguage\": \"$TEMPLATE_LANG\", \"bodyVariables\": [\"Test User\"]}}}" \
  --cookie cookie.txt \
  http://localhost:8000/graphql.php)

if ! echo "$SEND_RESPONSE" | grep -q "success"; then
  echo "❌ Échec de l'envoi"
  echo "$SEND_RESPONSE"
  exit 1
fi

SUCCESS=$(echo "$SEND_RESPONSE" | grep -o '"success":[^,}]*' | cut -d: -f2)

if [ "$SUCCESS" = "true" ]; then
  MESSAGE_ID=$(echo "$SEND_RESPONSE" | grep -o '"messageId":"[^"]*' | cut -d\" -f4)
  echo "✅ Message envoyé avec succès!"
  echo "ID du message: $MESSAGE_ID"
else
  ERROR=$(echo "$SEND_RESPONSE" | grep -o '"error":"[^"]*' | cut -d\" -f4)
  echo "❌ Échec de l'envoi: $ERROR"
  exit 1
fi

echo
echo "Tests terminés avec succès!"