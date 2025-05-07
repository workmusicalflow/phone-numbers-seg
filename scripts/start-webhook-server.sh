#!/bin/bash

# Script pour démarrer le serveur PHP avec webhook WhatsApp

# Vérifier si le port est spécifié
if [ -z "$1" ]; then
  PORT=8000
else
  PORT="$1"
fi

echo "Démarrage du serveur PHP sur le port $PORT..."
echo "Webhook disponible à: http://localhost:$PORT/whatsapp/webhook.php"
echo "Logs dans: /Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/"
echo "Utiliser Ctrl+C pour arrêter le serveur"
echo ""

cd /Users/ns2poportable/Desktop/phone-numbers-seg
php -S localhost:$PORT -t public