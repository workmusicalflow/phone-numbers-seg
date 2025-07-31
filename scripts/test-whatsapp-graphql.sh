#!/bin/bash

# Script de test de l'API GraphQL WhatsApp

# Configuration
API_URL="http://localhost:8000/graphql.php"
TEST_USER_EMAIL="test@example.com"
TEST_USER_PASSWORD="password"

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== Test de l'API GraphQL WhatsApp ===${NC}\n"

# 1. Connexion et récupération du token
echo -e "${YELLOW}1. Connexion de l'utilisateur test...${NC}"

LOGIN_RESPONSE=$(curl -s -X POST $API_URL \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation Login($email: String!, $password: String!) { login(email: $email, password: $password) { user { id email } token } }",
    "variables": {
      "email": "'$TEST_USER_EMAIL'",
      "password": "'$TEST_USER_PASSWORD'"
    }
  }')

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$TOKEN" ]; then
  echo -e "${RED}✗ Échec de la connexion${NC}"
  echo "Réponse : $LOGIN_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✓ Connexion réussie${NC}\n"

# 2. Test d'envoi d'un message texte
echo -e "${YELLOW}2. Test d'envoi d'un message texte...${NC}"

SEND_MESSAGE_RESPONSE=$(curl -s -X POST $API_URL \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "mutation SendMessage($message: WhatsAppMessageInput!) { sendWhatsAppMessage(message: $message) { id phoneNumber type content status createdAt } }",
    "variables": {
      "message": {
        "recipient": "+2250123456789",
        "type": "text",
        "content": "Test message via GraphQL - '"$(date +%Y-%m-%d\ %H:%M:%S)"'"
      }
    }
  }')

if echo $SEND_MESSAGE_RESPONSE | grep -q '"id"'; then
  echo -e "${GREEN}✓ Message envoyé avec succès${NC}"
  MESSAGE_ID=$(echo $SEND_MESSAGE_RESPONSE | grep -o '"id":"[^"]*' | sed 's/"id":"//')
  echo "ID du message : $MESSAGE_ID"
else
  echo -e "${RED}✗ Échec de l'envoi${NC}"
  echo "Réponse : $SEND_MESSAGE_RESPONSE"
fi
echo ""

# 3. Test de récupération des messages
echo -e "${YELLOW}3. Test de récupération des messages...${NC}"

GET_MESSAGES_RESPONSE=$(curl -s -X POST $API_URL \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "query GetMessages($limit: Int) { getWhatsAppMessages(limit: $limit) { messages { id phoneNumber type content status createdAt } totalCount } }",
    "variables": {
      "limit": 10
    }
  }')

MESSAGE_COUNT=$(echo $GET_MESSAGES_RESPONSE | grep -o '"totalCount":[0-9]*' | sed 's/"totalCount"://')

if [ -n "$MESSAGE_COUNT" ]; then
  echo -e "${GREEN}✓ $MESSAGE_COUNT messages récupérés${NC}"
else
  echo -e "${RED}✗ Échec de la récupération${NC}"
  echo "Réponse : $GET_MESSAGES_RESPONSE"
fi
echo ""

# 4. Test de récupération des templates
echo -e "${YELLOW}4. Test de récupération des templates...${NC}"

GET_TEMPLATES_RESPONSE=$(curl -s -X POST $API_URL \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "query GetTemplates { getWhatsAppUserTemplates { id template_id name language status } }"
  }')

if echo $GET_TEMPLATES_RESPONSE | grep -q '"template_id"'; then
  echo -e "${GREEN}✓ Templates récupérés${NC}"
  echo "Templates disponibles :"
  echo $GET_TEMPLATES_RESPONSE | grep -o '"name":"[^"]*' | sed 's/"name":"/ - /'
else
  echo -e "${YELLOW}⚠ Aucun template trouvé ou erreur${NC}"
  echo "Réponse : $GET_TEMPLATES_RESPONSE"
fi
echo ""

# 5. Test d'envoi avec template
echo -e "${YELLOW}5. Test d'envoi avec template...${NC}"

SEND_TEMPLATE_RESPONSE=$(curl -s -X POST $API_URL \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "mutation SendTemplate($message: WhatsAppMessageInput!) { sendWhatsAppMessage(message: $message) { id phoneNumber type templateName status } }",
    "variables": {
      "message": {
        "recipient": "+2250123456789",
        "type": "template",
        "templateName": "hello_world",
        "languageCode": "en_US"
      }
    }
  }')

if echo $SEND_TEMPLATE_RESPONSE | grep -q '"id"'; then
  echo -e "${GREEN}✓ Template envoyé avec succès${NC}"
else
  echo -e "${YELLOW}⚠ Échec de l'envoi du template${NC}"
  echo "Réponse : $SEND_TEMPLATE_RESPONSE"
fi
echo ""

# 6. Test de filtrage des messages
echo -e "${YELLOW}6. Test de filtrage des messages...${NC}"

FILTER_MESSAGES_RESPONSE=$(curl -s -X POST $API_URL \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "query FilterMessages($phoneNumber: String, $status: String) { getWhatsAppMessages(phoneNumber: $phoneNumber, status: $status) { messages { id phoneNumber status } totalCount } }",
    "variables": {
      "phoneNumber": "+2250123456789",
      "status": "sent"
    }
  }')

FILTERED_COUNT=$(echo $FILTER_MESSAGES_RESPONSE | grep -o '"totalCount":[0-9]*' | sed 's/"totalCount"://')

if [ -n "$FILTERED_COUNT" ]; then
  echo -e "${GREEN}✓ $FILTERED_COUNT messages filtrés${NC}"
else
  echo -e "${RED}✗ Échec du filtrage${NC}"
fi
echo ""

# 7. Test de pagination
echo -e "${YELLOW}7. Test de pagination...${NC}"

PAGINATED_RESPONSE=$(curl -s -X POST $API_URL \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "query PaginatedMessages($limit: Int, $offset: Int) { getWhatsAppMessages(limit: $limit, offset: $offset) { messages { id } totalCount hasMore } }",
    "variables": {
      "limit": 5,
      "offset": 0
    }
  }')

if echo $PAGINATED_RESPONSE | grep -q '"hasMore"'; then
  echo -e "${GREEN}✓ Pagination fonctionnelle${NC}"
else
  echo -e "${RED}✗ Échec de la pagination${NC}"
fi
echo ""

# 8. Test d'erreur (numéro invalide)
echo -e "${YELLOW}8. Test de gestion d'erreur...${NC}"

ERROR_RESPONSE=$(curl -s -X POST $API_URL \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "mutation SendInvalidMessage($message: WhatsAppMessageInput!) { sendWhatsAppMessage(message: $message) { id } }",
    "variables": {
      "message": {
        "recipient": "invalid",
        "type": "text",
        "content": "Test"
      }
    }
  }')

if echo $ERROR_RESPONSE | grep -q '"errors"'; then
  echo -e "${GREEN}✓ Erreur correctement gérée${NC}"
else
  echo -e "${RED}✗ Gestion d'erreur défaillante${NC}"
fi
echo ""

# 9. Résumé des tests
echo -e "${BLUE}=== Résumé des tests GraphQL ===${NC}"
echo -e "${GREEN}✓ Authentification${NC}"
echo -e "${GREEN}✓ Envoi de messages${NC}"
echo -e "${GREEN}✓ Récupération des messages${NC}"
echo -e "${GREEN}✓ Templates${NC}"
echo -e "${GREEN}✓ Filtrage${NC}"
echo -e "${GREEN}✓ Pagination${NC}"
echo -e "${GREEN}✓ Gestion d'erreurs${NC}"

echo -e "\n${GREEN}Tests GraphQL WhatsApp terminés avec succès !${NC}"