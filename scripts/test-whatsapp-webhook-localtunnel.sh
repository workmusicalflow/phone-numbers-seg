#!/bin/bash

# Script de test du webhook WhatsApp avec localtunnel

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== Test du webhook WhatsApp avec localtunnel ===${NC}\n"

# 1. Vérifier que localtunnel est installé
echo -e "${YELLOW}1. Vérification de localtunnel...${NC}"
if ! command -v lt &> /dev/null; then
    echo -e "${RED}✗ localtunnel n'est pas installé${NC}"
    echo "Installation : npm install -g localtunnel"
    exit 1
fi
echo -e "${GREEN}✓ localtunnel est installé${NC}\n"

# 2. Démarrer le serveur PHP local
echo -e "${YELLOW}2. Démarrage du serveur PHP local...${NC}"
PHP_PORT=8000
php -S localhost:$PHP_PORT -t public > /tmp/php-server.log 2>&1 &
PHP_PID=$!
sleep 2

if ps -p $PHP_PID > /dev/null; then
    echo -e "${GREEN}✓ Serveur PHP démarré sur le port $PHP_PORT (PID: $PHP_PID)${NC}\n"
else
    echo -e "${RED}✗ Échec du démarrage du serveur PHP${NC}"
    exit 1
fi

# 3. Démarrer localtunnel
echo -e "${YELLOW}3. Démarrage de localtunnel...${NC}"
lt --port $PHP_PORT > /tmp/localtunnel.log 2>&1 &
LT_PID=$!
sleep 5

# Extraire l'URL du tunnel
TUNNEL_URL=$(grep -o 'https://[a-z0-9-]*.loca.lt' /tmp/localtunnel.log | head -1)

if [ -z "$TUNNEL_URL" ]; then
    echo -e "${RED}✗ Impossible d'obtenir l'URL du tunnel${NC}"
    kill $PHP_PID
    exit 1
fi

echo -e "${GREEN}✓ Tunnel créé : $TUNNEL_URL${NC}\n"

# 4. Afficher les URLs de webhook
echo -e "${BLUE}=== URLs de webhook ===${NC}"
echo "URL de base : $TUNNEL_URL"
echo "URL du webhook : $TUNNEL_URL/whatsapp/webhook.php"
echo "URL de vérification : $TUNNEL_URL/whatsapp/webhook.php?hub.mode=subscribe&hub.verify_token=your-verify-token&hub.challenge=test"
echo ""

# 5. Test de vérification du webhook
echo -e "${YELLOW}4. Test de vérification du webhook...${NC}"
VERIFY_RESPONSE=$(curl -s "$TUNNEL_URL/whatsapp/webhook.php?hub.mode=subscribe&hub.verify_token=oracle_whatsapp_verify_token_2025&hub.challenge=test123")
if [ "$VERIFY_RESPONSE" = "test123" ]; then
    echo -e "${GREEN}✓ Vérification du webhook réussie${NC}\n"
else
    echo -e "${RED}✗ Échec de la vérification : $VERIFY_RESPONSE${NC}\n"
fi

# 6. Test d'envoi de webhook
echo -e "${YELLOW}5. Test d'envoi de webhook (status update)...${NC}"

# Payload de test pour une mise à jour de statut
WEBHOOK_PAYLOAD=$(cat <<EOF
{
  "entry": [{
    "id": "12345",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "metadata": {
          "display_phone_number": "15550555555",
          "phone_number_id": "12345"
        },
        "statuses": [{
          "id": "wamid.test123",
          "status": "delivered",
          "timestamp": "$(date +%s)",
          "recipient_id": "2250123456789"
        }]
      },
      "field": "messages"
    }]
  }]
}
EOF
)

# Envoyer le webhook
WEBHOOK_RESPONSE=$(curl -s -X POST "$TUNNEL_URL/whatsapp/webhook.php" \
    -H "Content-Type: application/json" \
    -d "$WEBHOOK_PAYLOAD")

echo "Réponse du webhook : $WEBHOOK_RESPONSE"
echo -e "${GREEN}✓ Webhook envoyé${NC}\n"

# 7. Test avec un message entrant
echo -e "${YELLOW}6. Test de webhook avec message entrant...${NC}"

INCOMING_PAYLOAD=$(cat <<EOF
{
  "entry": [{
    "id": "12345",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "metadata": {
          "display_phone_number": "15550555555",
          "phone_number_id": "12345"
        },
        "messages": [{
          "from": "2250123456789",
          "id": "wamid.incoming123",
          "timestamp": "$(date +%s)",
          "text": {
            "body": "Test message entrant"
          },
          "type": "text"
        }]
      },
      "field": "messages"
    }]
  }]
}
EOF
)

INCOMING_RESPONSE=$(curl -s -X POST "$TUNNEL_URL/whatsapp/webhook.php" \
    -H "Content-Type: application/json" \
    -d "$INCOMING_PAYLOAD")

echo "Réponse pour message entrant : $INCOMING_RESPONSE"
echo -e "${GREEN}✓ Message entrant traité${NC}\n"

# 8. Vérifier les logs
echo -e "${YELLOW}7. Vérification des logs...${NC}"
if [ -f "../var/logs/whatsapp/webhook_$(date +%Y-%m-%d)*.json" ]; then
    echo -e "${GREEN}✓ Logs webhook créés${NC}"
    echo "Dernières entrées :"
    tail -5 ../var/logs/whatsapp/webhook_*.json
else
    echo -e "${YELLOW}⚠ Pas de logs trouvés${NC}"
fi
echo ""

# 9. Instructions pour l'utilisateur
echo -e "${BLUE}=== Instructions ===${NC}"
echo "1. Configurez cette URL dans votre configuration WhatsApp Business :"
echo "   $TUNNEL_URL/whatsapp/webhook.php"
echo ""
echo "2. Utilisez ce token de vérification :"
echo "   oracle_whatsapp_verify_token_2025"
echo ""
echo "3. Pour tester l'envoi de messages, utilisez :"
echo "   php scripts/test-whatsapp-integration.php"
echo ""
echo "4. Pour arrêter les services :"
echo "   kill $PHP_PID $LT_PID"
echo ""
echo -e "${YELLOW}Les services restent actifs. Appuyez sur Ctrl+C pour arrêter.${NC}"

# Fonction de nettoyage
cleanup() {
    echo -e "\n${YELLOW}Arrêt des services...${NC}"
    kill $PHP_PID 2>/dev/null
    kill $LT_PID 2>/dev/null
    echo -e "${GREEN}Services arrêtés${NC}"
    exit 0
}

# Gérer l'interruption
trap cleanup SIGINT SIGTERM

# Garder le script actif
while true; do
    sleep 1
done