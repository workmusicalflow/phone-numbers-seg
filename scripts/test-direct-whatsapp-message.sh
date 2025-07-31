#!/bin/bash

# Script de test direct de l'envoi de message WhatsApp via GraphQL

# Configuration
API_URL="http://localhost:8000/graphql.php"
PHONE_NUMBER="+2250777104936"
MESSAGE="Test d'intégration WhatsApp depuis Oracle - $(date +%H:%M:%S)"

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== Test direct de l'envoi WhatsApp ===${NC}\n"

# 1. Connexion et récupération du token
echo -e "${YELLOW}1. Connexion pour obtenir le cookie de session...${NC}"

# Créer un fichier de cookies temporaire
COOKIE_FILE="/tmp/whatsapp_test_cookies.txt"

# Connexion
LOGIN_RESPONSE=$(curl -s -c "$COOKIE_FILE" -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation { login(username: \"admin\", password: \"password123\") }"
  }')

echo "Réponse de connexion : $LOGIN_RESPONSE"

# 2. Test d'envoi d'un message
echo -e "\n${YELLOW}2. Envoi d'un message WhatsApp...${NC}"

SEND_RESPONSE=$(curl -s -b "$COOKIE_FILE" -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d "{
    \"query\": \"mutation { sendWhatsAppMessage(message: { recipient: \\\"$PHONE_NUMBER\\\", type: \\\"text\\\", content: \\\"$MESSAGE\\\" }) { id phoneNumber type content status createdAt } }\"
  }")

echo "Réponse d'envoi : $SEND_RESPONSE"

# 3. Test de récupération des messages
echo -e "\n${YELLOW}3. Récupération des messages WhatsApp...${NC}"

LIST_RESPONSE=$(curl -s -b "$COOKIE_FILE" -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "query { getWhatsAppMessages(limit: 5) { totalCount messages { id phoneNumber type content status createdAt } } }"
  }')

echo "Liste des messages : $LIST_RESPONSE"

# Nettoyer
rm -f "$COOKIE_FILE"

echo -e "\n${GREEN}Test terminé${NC}"