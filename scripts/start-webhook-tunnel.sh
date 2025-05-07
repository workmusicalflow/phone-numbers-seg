#!/bin/bash

# Script pour exposer le webhook WhatsApp via Localtunnel

# Vérifier si le port est spécifié
if [ -z "$1" ]; then
  PORT=8000
else
  PORT="$1"
fi

echo "Démarrage du tunnel pour le port $PORT..."
npx localtunnel --port $PORT --subdomain oracle-whatsapp-webhook

# Note: La commande ci-dessus créera une URL publique comme:
# https://oracle-whatsapp-webhook.loca.lt
# Cette URL doit être configurée dans le panneau de configuration de l'API WhatsApp Business
# en ajoutant le chemin /whatsapp/webhook.php à la fin de l'URL