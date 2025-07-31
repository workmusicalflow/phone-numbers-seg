#!/bin/bash

echo "Test complet d'envoi de média depuis le frontend"
echo "=============================================="

# Se connecter
echo "1. Connexion..."
curl -s -c cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{
    "query": "mutation { login(username: \"admin\", password: \"admin123\") }"
  }' > /dev/null

# Créer un fichier test
TEST_IMAGE_PATH="/tmp/test_image.jpg"
echo "/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=" | base64 -d > "$TEST_IMAGE_PATH"

# Upload du fichier
echo -e "\n2. Upload du fichier..."
UPLOAD_RESPONSE=$(curl -s -b cookies.txt -X POST http://localhost:8000/api/whatsapp/upload.php \
  -H "Origin: http://localhost:5173" \
  -H "Accept: application/json" \
  -F "file=@$TEST_IMAGE_PATH;type=image/jpeg")

echo "Upload response: $UPLOAD_RESPONSE"

# Extraire le media ID
MEDIA_ID=$(echo "$UPLOAD_RESPONSE" | sed -n 's/.*"mediaId":"\([^"]*\)".*/\1/p')
echo "Media ID: $MEDIA_ID"

# Envoyer le média via GraphQL
echo -e "\n3. Envoi du média..."
SEND_RESPONSE=$(curl -s -b cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d "{
    \"query\": \"mutation SendMediaMessage(\$recipient: String!, \$type: String!, \$mediaIdOrUrl: String!, \$caption: String) { sendWhatsAppMediaMessage(recipient: \$recipient, type: \$type, mediaIdOrUrl: \$mediaIdOrUrl, caption: \$caption) { id wabaMessageId phoneNumber direction type content status timestamp mediaId createdAt updatedAt } }\",
    \"variables\": {
      \"recipient\": \"+2250748221590\",
      \"type\": \"image\",
      \"mediaIdOrUrl\": \"$MEDIA_ID\",
      \"caption\": \"Test complet depuis le frontend\"
    }
  }")

echo "Send response:"
echo "$SEND_RESPONSE" | json_pp || echo "$SEND_RESPONSE"

# Vérifier le succès
if echo "$SEND_RESPONSE" | grep -q '"wabaMessageId"'; then
  echo -e "\n✅ Test réussi ! Le média a été envoyé avec succès."
else
  echo -e "\n❌ Test échoué. Vérifiez les logs."
fi

# Nettoyer
rm -f cookies.txt "$TEST_IMAGE_PATH"