#!/bin/bash

# Script pour configurer et démarrer l'intégration WhatsApp

# Vérification des prérequis
echo "Vérification des prérequis..."
command -v php >/dev/null 2>&1 || { echo "PHP est requis mais n'est pas installé. Aborting."; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "Node.js est requis mais n'est pas installé. Aborting."; exit 1; }

# Configurer le port
PORT=8000

# Définir les chemins
BASE_DIR="/Users/ns2poportable/Desktop/phone-numbers-seg"
SCRIPTS_DIR="$BASE_DIR/scripts"
LOGS_DIR="$BASE_DIR/var/logs"

# Créer les répertoires nécessaires
mkdir -p "$LOGS_DIR"

# Vérifier si la table existe déjà
echo "Vérification et création des tables WhatsApp..."
php "$SCRIPTS_DIR/create-whatsapp-tables.php"

# Démarrer le serveur et le tunnel en parallèle
echo "Démarrage de l'intégration WhatsApp..."
echo "---------------------------------------------------"
echo "1. Dans un autre terminal, exécutez:"
echo "   $SCRIPTS_DIR/start-webhook-server.sh $PORT"
echo ""
echo "2. Puis dans un troisième terminal, exécutez:"
echo "   $SCRIPTS_DIR/start-webhook-tunnel.sh $PORT"
echo ""
echo "3. Pour tester le webhook localement, exécutez:"
echo "   $SCRIPTS_DIR/test-whatsapp-webhook.sh"
echo "---------------------------------------------------"
echo ""
echo "Documentation disponible dans:"
echo "$BASE_DIR/docs/whatsapp-webhook-testing.md"
echo ""
echo "Prêt à démarrer!"