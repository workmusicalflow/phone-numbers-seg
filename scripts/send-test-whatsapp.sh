#!/bin/bash

# Script pour envoyer un message test WhatsApp avec le format de numéro corrigé
# Date: 21 mai 2025

# Charger les configurations
source "$(dirname "$0")/../.env.whatsapp.example"

# Configuration
META_API_URL="https://graph.facebook.com/${WHATSAPP_API_VERSION}"
PHONE_NUMBER_ID="${WHATSAPP_PHONE_NUMBER_ID}"
ACCESS_TOKEN="${WHATSAPP_ACCESS_TOKEN}"
LOG_FILE="logs/whatsapp-test-$(date +%Y%m%d-%H%M%S).log"

# Créer le répertoire de logs s'il n'existe pas
mkdir -p "$(dirname "$LOG_FILE")"

# Numéro de téléphone à utiliser (format corrigé)
# Tester spécifiquement avec le numéro complet incluant le code pays complet
RECIPIENT="+2250777104936"

# Message d'information
echo "==================================================" | tee -a "$LOG_FILE"
echo "Test d'envoi de message WhatsApp" | tee -a "$LOG_FILE"
echo "Date: $(date)" | tee -a "$LOG_FILE"
echo "Destinataire: $RECIPIENT" | tee -a "$LOG_FILE"
echo "==================================================" | tee -a "$LOG_FILE"

# Tester en mode debug d'abord (pas d'envoi réel)
echo "Test en mode debug (pas d'envoi réel)..." | tee -a "$LOG_FILE"
debug_result=$(curl -s -X POST "${META_API_URL}/${PHONE_NUMBER_ID}/messages?debug=all" \
    -H "Authorization: Bearer ${ACCESS_TOKEN}" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "'$RECIPIENT'",
        "type": "template",
        "template": {
            "name": "connection_check",
            "language": {
                "code": "fr"
            }
        }
    }')

echo "Résultat debug: $debug_result" | tee -a "$LOG_FILE"

# Vérifier si le mode debug a réussi
if echo "$debug_result" | grep -q "error"; then
    echo "❌ Erreur en mode debug. Abandon de l'envoi réel." | tee -a "$LOG_FILE"
    echo "Vérifiez le format du numéro ou les paramètres de l'API." | tee -a "$LOG_FILE"
    exit 1
else
    echo "✅ Mode debug réussi. Procédure d'envoi réel..." | tee -a "$LOG_FILE"
fi

# Envoi réel du message
echo "Envoi du message..." | tee -a "$LOG_FILE"
result=$(curl -s -X POST "${META_API_URL}/${PHONE_NUMBER_ID}/messages" \
    -H "Authorization: Bearer ${ACCESS_TOKEN}" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "'$RECIPIENT'",
        "type": "template",
        "template": {
            "name": "connection_check",
            "language": {
                "code": "fr"
            }
        }
    }')

echo "Résultat: $result" | tee -a "$LOG_FILE"

# Vérifier si l'envoi a réussi
if echo "$result" | grep -q "messages"; then
    # Extraire l'ID du message et le wa_id
    message_id=$(echo "$result" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)
    wa_id=$(echo "$result" | grep -o '"wa_id":"[^"]*"' | head -1 | cut -d'"' -f4)
    
    echo "✅ Message envoyé avec succès!" | tee -a "$LOG_FILE"
    echo "Message ID: $message_id" | tee -a "$LOG_FILE"
    echo "WhatsApp ID: $wa_id" | tee -a "$LOG_FILE"
    
    echo ""
    echo "IMPORTANT: Vérifiez si vous avez reçu le message sur votre téléphone."
    echo "Numéro envoyé: $RECIPIENT"
    echo "Numéro reconnu par WhatsApp: wa_id=$wa_id"
    
    # Ajouter une entrée dans la base de données si possible
    if which php > /dev/null 2>&1; then
        echo ""
        echo "Tentative d'ajout dans la table d'historique..." | tee -a "$LOG_FILE"
        php - <<EOF
<?php
try {
    // Connexion à la base de données
    \$dbPath = __DIR__ . '/../var/database.sqlite';
    \$pdo = new PDO('sqlite:' . \$dbPath);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Préparation de la requête
    \$stmt = \$pdo->prepare("
        INSERT INTO whatsapp_message_history 
        (recipient_phone, template_name, message_id, wa_id, status, created_at)
        VALUES (?, ?, ?, ?, 'sent', datetime('now'))
    ");
    
    // Exécution de la requête
    \$stmt->execute(['$RECIPIENT', 'connection_check', '$message_id', '$wa_id']);
    
    echo "✅ Entrée ajoutée à la table whatsapp_message_history\n";
} catch (PDOException \$e) {
    echo "❌ Erreur lors de l'ajout à l'historique: " . \$e->getMessage() . "\n";
}
EOF
    fi
    
    exit 0
else
    echo "❌ Échec de l'envoi du message" | tee -a "$LOG_FILE"
    echo "Vérifiez les logs pour plus de détails." | tee -a "$LOG_FILE"
    exit 1
fi