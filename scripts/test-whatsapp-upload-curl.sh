#!/bin/bash

# Test de l'upload WhatsApp via curl

echo "Test de l'upload WhatsApp via curl"
echo "================================="

# Créer un fichier test
TEST_IMAGE_PATH="/tmp/test_whatsapp_image.jpg"
echo "/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=" | base64 -d > "$TEST_IMAGE_PATH"

# Obtenir le cookie de session
echo "Connexion pour obtenir le cookie de session..."
LOGIN_RESPONSE=$(curl -s -c cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{
    "query": "mutation { login(username: \"admin\", password: \"admin123\") }"
  }')
  
echo "Réponse de connexion: $LOGIN_RESPONSE"

# Tester l'upload avec CORS
echo -e "\nTest de l'upload avec CORS..."
curl -v -b cookies.txt -X POST http://localhost:8000/api/whatsapp/upload.php \
  -H "Origin: http://localhost:5173" \
  -H "Accept: application/json" \
  -F "file=@$TEST_IMAGE_PATH;type=image/jpeg" \
  2>&1 | grep -E "(< HTTP|< Access-Control|{|error|success)"

# Nettoyer
rm -f "$TEST_IMAGE_PATH" cookies.txt