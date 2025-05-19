#!/bin/bash

echo "Test simple mutation"
echo "==================="

# Se connecter
echo "1. Connexion..."
curl -s -c cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{
    "query": "mutation { login(username: \"admin\", password: \"admin123\") }"
  }' > /dev/null

# Test simple
echo -e "\n2. Test mutation sendWhatsAppMediaMessage..."
curl -s -b cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{
    "query": "mutation { sendWhatsAppMediaMessage(recipient: \"+22501234567\", type: \"image\", mediaIdOrUrl: \"123456\", caption: \"Test\") { id wabaMessageId phoneNumber } }"
  }' | json_pp

# Nettoyer
rm -f cookies.txt