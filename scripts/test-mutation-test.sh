#!/bin/bash

echo "Test mutation de test"
echo "===================="

# Se connecter
echo "1. Connexion..."
curl -s -c cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{
    "query": "mutation { login(username: \"admin\", password: \"admin123\") }"
  }' > /dev/null

# Test simple
echo -e "\n2. Test mutation testWhatsAppMutation..."
curl -s -b cookies.txt -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{
    "query": "mutation { testWhatsAppMutation }"
  }' | json_pp

# Nettoyer
rm -f cookies.txt