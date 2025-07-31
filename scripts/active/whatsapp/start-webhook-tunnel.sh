#!/bin/bash

# Script pour exposer le webhook WhatsApp via Localtunnel

# Configuration par défaut
PORT=${1:-8000}
SUBDOMAIN=${2:-"oracle-whatsapp-webhook"}

echo "=== Tunnel WhatsApp Webhook ==="
echo "Port local : $PORT"
echo "Sous-domaine : $SUBDOMAIN"
echo ""

# Vérification que le serveur local est actif
if ! nc -z localhost $PORT; then
    echo "⚠️  Aucun serveur n'écoute sur le port $PORT"
    echo "Veuillez d'abord démarrer le serveur avec : ./scripts/start-webhook-server.sh $PORT"
    exit 1
fi

echo "Démarrage du tunnel..."
echo "URL du webhook : https://$SUBDOMAIN.loca.lt/whatsapp/webhook.php"
echo ""
echo "Configuration Meta:"
echo "1. Allez dans votre app Meta > WhatsApp > Configuration"
echo "2. URL de callback : https://$SUBDOMAIN.loca.lt/whatsapp/webhook.php"
echo "3. Token de vérification : utilisez la valeur de WHATSAPP_WEBHOOK_VERIFY_TOKEN"
echo ""
echo "Appuyez sur Ctrl+C pour arrêter le tunnel"
echo ""

npx localtunnel --port $PORT --subdomain $SUBDOMAIN --print-requests