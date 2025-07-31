#!/bin/bash

CONTACTS_FILE="contacts.txt"
TEMPLATE_NAME="qshe_day_3"
TEMPLATE_LANGUAGE="fr"
HEADER_MEDIA_URL="https://events-qualitas-ci.com/public/images/wha-Image1.jpeg"
API_URL="http://localhost:8000/api/whatsapp/send-template-v2.php"

# Vérifier que le fichier existe
if [ ! -f "$CONTACTS_FILE" ]; then
    echo "❌ Fichier $CONTACTS_FILE introuvable !"
    exit 1
fi

# Lire le fichier ligne par ligne
while IFS=',' read -r number name; do
    # Ignorer les lignes vides et commentaires
    [[ -z "$number" || "$number" =~ ^#.*$ ]] && continue
    
    echo "📱 Envoi vers $number ($name)..."
    
    curl -X POST \
        -H "Content-Type: application/json" \
        -d "{
            \"recipientPhoneNumber\":\"$number\",
            \"templateName\":\"$TEMPLATE_NAME\",
            \"templateLanguage\":\"$TEMPLATE_LANGUAGE\",
            \"headerMediaUrl\":\"$HEADER_MEDIA_URL\"
        }" \
        "$API_URL"
    
    echo "✅ Terminé pour $number"
    sleep 1
    
done < "$CONTACTS_FILE"