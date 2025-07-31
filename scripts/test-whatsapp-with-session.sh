#!/bin/bash

# Fichier pour stocker les cookies
COOKIE_FILE="./cookie.txt"

# D'abord, se connecter et obtenir la session
echo "Connexion en cours..."
curl -s -c "$COOKIE_FILE" -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation Login($username: String!, $password: String!) { login(username: $username, password: $password) }",
    "variables": {
      "username": "admin",
      "password": "admin123"
    }
  }'

echo -e "\nCookies enregistrés dans $COOKIE_FILE"

# Ensuite, faire la requête avec les cookies
echo -e "\nRequête WhatsApp messages..."
curl -s -b "$COOKIE_FILE" -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -d '{
    "query": "query GetWhatsAppMessages($limit: Int, $offset: Int) { getWhatsAppMessages(limit: $limit, offset: $offset) { messages { id phoneNumber direction type status } totalCount hasMore } }",
    "variables": {
      "limit": 5,
      "offset": 0
    }
  }'