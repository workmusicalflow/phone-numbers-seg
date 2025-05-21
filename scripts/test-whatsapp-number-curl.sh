#!/bin/bash

# Script pour tester directement l'API WhatsApp avec curl
# Teste plusieurs formats de numéros de téléphone

# Configuration - à modifier selon les besoins
PHONE_NUMBER_ID=$(grep -o 'phone_number_id.*=>.*' /Users/ns2poportable/Desktop/phone-numbers-seg/src/config/whatsapp.php | cut -d "'" -f 2)
ACCESS_TOKEN=$(grep -o 'access_token.*=>.*' /Users/ns2poportable/Desktop/phone-numbers-seg/src/config/whatsapp.php | cut -d "'" -f 2)
API_VERSION="v18.0"  # Vérifier la version correcte

# Verification des variables requises
if [ -z "$PHONE_NUMBER_ID" ] || [ -z "$ACCESS_TOKEN" ]; then
    echo "Erreur: PHONE_NUMBER_ID ou ACCESS_TOKEN non trouvé"
    echo "Veuillez les définir dans le fichier src/config/whatsapp.php"
    exit 1
fi

echo "Configuration:"
echo "Phone Number ID: $PHONE_NUMBER_ID"
echo "API Version: $API_VERSION"

# Formats de numéros à tester
declare -a NUMBER_FORMATS=(
    "+2250777104936"
    "2250777104936"
    "+225 0777104936"
    "0777104936"
    "777104936"
    "225777104936"
    "+225-077-710-4936"
)

# Dossier pour les résultats
RESULTS_DIR="/Users/ns2poportable/Desktop/phone-numbers-seg/logs/curl-tests"
mkdir -p "$RESULTS_DIR"

TIMESTAMP=$(date +"%Y%m%d-%H%M%S")
RESULTS_FILE="$RESULTS_DIR/whatsapp-curl-test-$TIMESTAMP.log"

echo "=== Test d'envoi de messages WhatsApp avec curl ===" | tee -a "$RESULTS_FILE"
echo "Date: $(date)" | tee -a "$RESULTS_FILE"
echo "Les résultats seront enregistrés dans $RESULTS_FILE" | tee -a "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

# Fonction pour tester un format de numéro
test_number_format() {
    local number="$1"
    local description="$2"
    
    echo "=== Test du format: $description ($number) ===" | tee -a "$RESULTS_FILE"
    
    # Créer le payload JSON
    PAYLOAD="{
        \"messaging_product\": \"whatsapp\",
        \"to\": \"$number\",
        \"type\": \"text\",
        \"text\": {
            \"body\": \"Test via curl: $description\"
        }
    }"
    
    # Exécuter la requête curl
    HTTP_RESPONSE=$(curl -s -w "\\n%{http_code}" \
        -X POST \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD" \
        "https://graph.facebook.com/$API_VERSION/$PHONE_NUMBER_ID/messages")
    
    # Extraire le code de statut HTTP et le corps de la réponse
    HTTP_BODY=$(echo "$HTTP_RESPONSE" | head -n -1)
    HTTP_STATUS=$(echo "$HTTP_RESPONSE" | tail -n 1)
    
    echo "Statut HTTP: $HTTP_STATUS" | tee -a "$RESULTS_FILE"
    echo "Réponse:" | tee -a "$RESULTS_FILE"
    echo "$HTTP_BODY" | tee -a "$RESULTS_FILE"
    
    # Analyser la réponse JSON pour obtenir message_id si disponible
    if [ "$HTTP_STATUS" -eq 200 ]; then
        MESSAGE_ID=$(echo "$HTTP_BODY" | grep -o '"id":"[^"]*"' | cut -d'"' -f4)
        if [ -n "$MESSAGE_ID" ]; then
            echo "✓ Message envoyé avec succès! ID: $MESSAGE_ID" | tee -a "$RESULTS_FILE"
            echo "SUCCÈS: $number,$description,$MESSAGE_ID" >> "$RESULTS_DIR/whatsapp-successful-formats.csv"
        else
            echo "✗ Réponse 200 mais aucun ID de message trouvé" | tee -a "$RESULTS_FILE"
        fi
    else
        ERROR_MSG=$(echo "$HTTP_BODY" | grep -o '"message":"[^"]*"' | cut -d'"' -f4)
        if [ -n "$ERROR_MSG" ]; then
            echo "✗ Échec de l'envoi: $ERROR_MSG" | tee -a "$RESULTS_FILE"
            echo "ÉCHEC: $number,$description,$ERROR_MSG" >> "$RESULTS_DIR/whatsapp-failed-formats.csv"
        else
            echo "✗ Échec de l'envoi avec status $HTTP_STATUS" | tee -a "$RESULTS_FILE"
            echo "ÉCHEC: $number,$description,HTTP $HTTP_STATUS" >> "$RESULTS_DIR/whatsapp-failed-formats.csv"
        fi
    fi
    
    echo "" | tee -a "$RESULTS_FILE"
    # Pause pour éviter les limites de rate
    sleep 2
}

# Initialiser les fichiers CSV
echo "Numéro,Description,Message ID" > "$RESULTS_DIR/whatsapp-successful-formats.csv"
echo "Numéro,Description,Erreur" > "$RESULTS_DIR/whatsapp-failed-formats.csv"

# Tester chaque format
test_number_format "+2250777104936" "Format international avec + (E.164)"
test_number_format "2250777104936" "Format international sans +"
test_number_format "+225 0777104936" "Format avec espace après code pays"
test_number_format "0777104936" "Format local avec 0 initial"
test_number_format "777104936" "Format sans 0 initial"
test_number_format "225777104936" "Format avec 225 et sans 0"
test_number_format "2250777104936" "Format avec 225 et avec 0"
test_number_format "+225-077-710-4936" "Format avec tirets"
test_number_format "+225 077 710 4936" "Format avec espaces"
test_number_format "(+225) 0777104936" "Format avec parenthèses"

# Synthèse des résultats
echo "=== SYNTHÈSE DES RÉSULTATS ===" | tee -a "$RESULTS_FILE"
echo "Formats qui ont fonctionné:" | tee -a "$RESULTS_FILE"
cat "$RESULTS_DIR/whatsapp-successful-formats.csv" | tee -a "$RESULTS_FILE"

echo "" | tee -a "$RESULTS_FILE"
echo "Formats qui ont échoué:" | tee -a "$RESULTS_FILE"
cat "$RESULTS_DIR/whatsapp-failed-formats.csv" | tee -a "$RESULTS_FILE"

echo "" | tee -a "$RESULTS_FILE"
echo "Recommandations basées sur les résultats:" | tee -a "$RESULTS_FILE"

# Compter le nombre de succès
SUCCESS_COUNT=$(grep -c "SUCCÈS" "$RESULTS_DIR/whatsapp-successful-formats.csv")

if [ "$SUCCESS_COUNT" -gt 0 ]; then
    echo "Format(s) recommandé(s) pour l'API WhatsApp:" | tee -a "$RESULTS_FILE"
    grep "SUCCÈS" "$RESULTS_DIR/whatsapp-successful-formats.csv" | cut -d ":" -f 2 | cut -d "," -f 1 | while read -r format; do
        echo "- $format" | tee -a "$RESULTS_FILE"
    done
else
    echo "Aucun format n'a fonctionné. Vérifiez les autorisations de l'API ou la configuration du compte." | tee -a "$RESULTS_FILE"
fi

echo "" | tee -a "$RESULTS_FILE"
echo "Test terminé. Consultez $RESULTS_FILE pour les détails complets."