#!/bin/bash

echo "Test de la mutation sendWhatsAppMediaMessage"
echo "=========================================="

# Se connecter
echo "1. Connexion..."
curl -s -c cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{
    "query": "mutation { login(username: \"admin\", password: \"admin123\") }"
  }' > /dev/null

# Créer et uploader un fichier test
echo "2. Upload du fichier test..."
TEST_IMAGE_PATH="/tmp/test_image.jpg"
echo "/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=" | base64 -d > "$TEST_IMAGE_PATH"

UPLOAD_RESPONSE=$(curl -s -b cookies.txt -X POST http://localhost:8000/api/whatsapp/upload.php \
  -H "Origin: http://localhost:5173" \
  -H "Accept: application/json" \
  -F "file=@$TEST_IMAGE_PATH;type=image/jpeg")

echo "Upload response: $UPLOAD_RESPONSE"

# Extraire le media ID
MEDIA_ID=$(echo "$UPLOAD_RESPONSE" | sed -n 's/.*"mediaId":"\([^"]*\)".*/\1/p')
echo "Media ID: $MEDIA_ID"

# Tester la mutation GraphQL
echo -e "\n3. Test de la mutation sendWhatsAppMediaMessage..."
MUTATION_RESPONSE=$(curl -s -b cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d "{
    \"query\": \"mutation SendMediaMessage(\$recipient: String!, \$type: String!, \$mediaIdOrUrl: String!, \$caption: String) { sendWhatsAppMediaMessage(recipient: \$recipient, type: \$type, mediaIdOrUrl: \$mediaIdOrUrl, caption: \$caption) { id wabaMessageId phoneNumber direction type content status timestamp mediaId createdAt updatedAt } }\",
    \"variables\": {
      \"recipient\": \"+22501234567\",
      \"type\": \"image\",
      \"mediaIdOrUrl\": \"$MEDIA_ID\",
      \"caption\": \"Test image depuis la mutation GraphQL\"
    }
  }")

echo "Mutation response:"
echo "$MUTATION_RESPONSE" | json_pp || echo "$MUTATION_RESPONSE"

# Nettoyer
rm -f cookies.txt "$TEST_IMAGE_PATH"