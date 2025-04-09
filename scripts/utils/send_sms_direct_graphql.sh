#!/bin/bash

# Script pour envoyer un SMS directement aux numéros du fichier CSV en utilisant GraphQL
# Ce script utilise la mutation sendSms qui a été testée et fonctionne

# ID de l'utilisateur AfricaQSHE
USER_ID=2

# Message à envoyer
MESSAGE="Test SMS envoyé via GraphQL"

# Numéros de téléphone extraits du fichier CSV
PHONE_NUMBERS=(
  "+2250777104936"  # CVPA
  "+2250788548900"  # GYM CENTER MINIKAN
)

echo "==================================================="
echo "  ENVOI DE SMS VIA GRAPHQL"
echo "==================================================="
echo ""
echo "Utilisateur: AfricaQSHE (ID: $USER_ID)"
echo "Message: $MESSAGE"
echo ""
echo "Envoi en cours..."
echo ""

# Fonction pour envoyer un SMS à un numéro
send_sms() {
  local phone_number=$1
  local message=$2
  local user_id=$3
  
  # Construire la requête GraphQL
  local query="{\"query\":\"mutation { sendSms(phoneNumber: \\\"$phone_number\\\", message: \\\"$message\\\", userId: $user_id) { id phoneNumber message status createdAt } }\"}"
  
  # Envoyer la requête GraphQL
  local response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    --data "$query" \
    "http://localhost:8000/graphql.php")
  
  # Afficher la réponse
  echo "Réponse pour $phone_number:"
  echo "$response" | grep -o '"status":"[^"]*"' | sed 's/"status":"/Status: /g' | sed 's/"//g'
  
  # Vérifier si le SMS a été envoyé avec succès
  if echo "$response" | grep -q '"status":"SENT"'; then
    echo "✅ SMS envoyé avec succès à $phone_number"
  else
    echo "❌ Échec de l'envoi du SMS à $phone_number"
    echo "Détails: $response"
  fi
  
  echo ""
}

# Envoyer un SMS à chaque numéro
for number in "${PHONE_NUMBERS[@]}"; do
  echo "Envoi à $number..."
  send_sms "$number" "$MESSAGE" "$USER_ID"
done

echo "==================================================="
echo "  ENVOI TERMINÉ"
echo "==================================================="
echo ""
echo "Pour vérifier les SMS envoyés, vous pouvez:"
echo "1. Consulter l'historique des SMS dans l'interface utilisateur"
echo "2. Exécuter la requête SQL suivante:"
echo "   sqlite3 src/database/database.sqlite \"SELECT * FROM sms_history WHERE user_id = $USER_ID ORDER BY created_at DESC LIMIT 10;\""
echo ""
