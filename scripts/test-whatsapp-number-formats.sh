#!/bin/bash

# Script pour tester différents formats de numéros de téléphone avec l'API WhatsApp
# Date: 21 mai 2025

# Charger les configurations
source "$(dirname "$0")/../.env.whatsapp.example"

# Configuration
META_API_URL="https://graph.facebook.com/${WHATSAPP_API_VERSION}"
PHONE_NUMBER_ID="${WHATSAPP_PHONE_NUMBER_ID}"
ACCESS_TOKEN="${WHATSAPP_ACCESS_TOKEN}"
LOG_FILE="logs/phone-format-test-$(date +%Y%m%d-%H%M%S).log"

# Créer le répertoire de logs s'il n'existe pas
mkdir -p "$(dirname "$LOG_FILE")"

# Fonction pour envoyer un message avec un format de numéro spécifique
test_phone_format() {
  local format_name=$1
  local phone_number=$2
  local template_name=${3:-"connection_check"}
  
  echo "Test du format: $format_name ($phone_number)" | tee -a "$LOG_FILE"
  
  # Envoyer le message en mode debug (pas d'envoi réel)
  local debug_result=$(curl -s -X POST "${META_API_URL}/${PHONE_NUMBER_ID}/messages?debug=all" \
      -H "Authorization: Bearer ${ACCESS_TOKEN}" \
      -H "Content-Type: application/json" \
      -d '{
          "messaging_product": "whatsapp",
          "to": "'$phone_number'",
          "type": "template",
          "template": {
              "name": "'$template_name'",
              "language": {
                  "code": "fr"
              }
          }
      }')
  
  echo "Résultat debug: $debug_result" | tee -a "$LOG_FILE"
  
  # Vérifier s'il y a une erreur dans la réponse debug
  if echo "$debug_result" | grep -q "error"; then
    echo "❌ Format invalide: $format_name" | tee -a "$LOG_FILE"
    return 1
  else
    echo "✅ Format valide en debug: $format_name" | tee -a "$LOG_FILE"
  fi
  
  # Si le mode debug est OK, faire un envoi réel
  echo "Envoi réel au format: $format_name ($phone_number)" | tee -a "$LOG_FILE"
  
  local result=$(curl -s -X POST "${META_API_URL}/${PHONE_NUMBER_ID}/messages" \
      -H "Authorization: Bearer ${ACCESS_TOKEN}" \
      -H "Content-Type: application/json" \
      -d '{
          "messaging_product": "whatsapp",
          "to": "'$phone_number'",
          "type": "template",
          "template": {
              "name": "'$template_name'",
              "language": {
                  "code": "fr"
              }
          }
      }')
  
  echo "Résultat: $result" | tee -a "$LOG_FILE"
  
  # Vérifier la réponse pour l'identifiant du message
  if echo "$result" | grep -q "messages"; then
    # Extraire l'ID du message et le wa_id
    local message_id=$(echo "$result" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)
    local wa_id=$(echo "$result" | grep -o '"wa_id":"[^"]*"' | head -1 | cut -d'"' -f4)
    echo "✅ Message envoyé avec succès!" | tee -a "$LOG_FILE"
    echo "Message ID: $message_id" | tee -a "$LOG_FILE"
    echo "WhatsApp ID: $wa_id" | tee -a "$LOG_FILE"
    echo "-------------------------------------------------" | tee -a "$LOG_FILE"
    
    # Ajouter une entrée dans la table d'historique si disponible
    if [ -f "$(dirname "$0")/add-whatsapp-history.php" ]; then
      php "$(dirname "$0")/add-whatsapp-history.php" "$phone_number" "$template_name" "$message_id" "$wa_id"
    fi
    
    return 0
  else
    echo "❌ Échec de l'envoi du message" | tee -a "$LOG_FILE"
    echo "-------------------------------------------------" | tee -a "$LOG_FILE"
    return 1
  fi
}

# En-tête du log
echo "==================================================" | tee -a "$LOG_FILE"
echo "Test des formats de numéros WhatsApp" | tee -a "$LOG_FILE"
echo "Date: $(date)" | tee -a "$LOG_FILE"
echo "==================================================" | tee -a "$LOG_FILE"

# Numéro de base à tester
BASE_NUMBER="0777104936"
COUNTRY_CODE="225"

# Tester différents formats
test_phone_format "Format E.164 avec préfixe +" "+${COUNTRY_CODE}${BASE_NUMBER}"
test_phone_format "Format E.164 sans préfixe +" "${COUNTRY_CODE}${BASE_NUMBER}"
test_phone_format "Sans zéro initial avec préfixe +" "+${COUNTRY_CODE}777104936"
test_phone_format "Sans zéro initial sans préfixe +" "${COUNTRY_CODE}777104936"
test_phone_format "Format avec espaces" "+${COUNTRY_CODE} 77 710 49 36"
test_phone_format "Format avec tirets" "+${COUNTRY_CODE}-77-710-49-36"

# Formats spécifiques au pays (Côte d'Ivoire)
test_phone_format "Format local avec zéro" "0${BASE_NUMBER}"
test_phone_format "Format local sans zéro" "${BASE_NUMBER#0}"
test_phone_format "Format suggéré par l'API précédemment" "22577104936"

echo "==================================================" | tee -a "$LOG_FILE"
echo "Tests terminés. Résultats dans $LOG_FILE" | tee -a "$LOG_FILE"
echo "==================================================" | tee -a "$LOG_FILE"

# Créer un script PHP pour ajouter une entrée dans la table d'historique
cat > "$(dirname "$0")/add-whatsapp-history.php" << 'EOF'
<?php
// Vérifier les arguments
if ($argc < 5) {
    die("Usage: php add-whatsapp-history.php <phone_number> <template_name> <message_id> <wa_id>\n");
}

// Récupérer les arguments
$phoneNumber = $argv[1];
$templateName = $argv[2];
$messageId = $argv[3];
$waId = $argv[4];

try {
    // Connexion à la base de données
    $dbPath = __DIR__ . '/../var/database.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Préparation de la requête
    $stmt = $pdo->prepare("
        INSERT INTO whatsapp_message_history 
        (recipient_phone, template_name, message_id, wa_id, status, created_at)
        VALUES (?, ?, ?, ?, 'sent', datetime('now'))
    ");
    
    // Exécution de la requête
    $stmt->execute([$phoneNumber, $templateName, $messageId, $waId]);
    
    echo "✅ Entrée ajoutée à la table whatsapp_message_history\n";
} catch (PDOException $e) {
    echo "❌ Erreur lors de l'ajout à l'historique: " . $e->getMessage() . "\n";
}
EOF

chmod +x "$(dirname "$0")/add-whatsapp-history.php"

echo "Script d'historique créé: $(dirname "$0")/add-whatsapp-history.php"