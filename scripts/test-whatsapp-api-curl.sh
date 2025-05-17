#!/bin/bash

# D'abord, se connecter pour obtenir un token
echo "Connexion en cours..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation Login($username: String!, $password: String!) { login(username: $username, password: $password) }",
    "variables": {
      "username": "admin",
      "password": "admin123"
    }
  }')

echo "Réponse de connexion: $LOGIN_RESPONSE"

# Extraire le token
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$TOKEN" ]; then
    echo "Erreur: Impossible d'obtenir le token"
    exit 1
fi

echo -e "\nToken obtenu: $TOKEN"

# Maintenant, faire la requête WhatsApp
echo -e "\nRequête WhatsApp messages..."
WHATSAPP_RESPONSE=$(curl -s -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "query GetWhatsAppMessages($limit: Int, $offset: Int) { getWhatsAppMessages(limit: $limit, offset: $offset) { messages { id phoneNumber direction type status } totalCount hasMore } }",
    "variables": {
      "limit": 5,
      "offset": 0
    }
  }')

echo -e "\nRéponse WhatsApp: $WHATSAPP_RESPONSE"