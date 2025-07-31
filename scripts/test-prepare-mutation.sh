#!/bin/bash

# Test de la mutation prepareWhatsAppBulkSend

# D'abord, se connecter pour obtenir un token
echo "=== Login ==="
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation { login(username: \"admin\", password: \"admin123\") { token user { id username } } }"
  }')

echo "Login response:"
echo "$LOGIN_RESPONSE"

# Extract token using grep and sed
TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ "$TOKEN" = "null" ]; then
  echo "Failed to get token"
  exit 1
fi

echo -e "\n=== Prepare Bulk Send ==="
PREPARE_RESPONSE=$(curl -s -X POST http://localhost:8000/graphql.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "mutation PrepareWhatsAppBulkSend($contactIds: [String!], $groupIds: [String!], $segmentIds: [String!], $phoneNumbers: [String!], $templateId: String!, $parameters: JSON, $priority: Int, $scheduledAt: String) { prepareWhatsAppBulkSend(contactIds: $contactIds, groupIds: $groupIds, segmentIds: $segmentIds, phoneNumbers: $phoneNumbers, templateId: $templateId, parameters: $parameters, priority: $priority, scheduledAt: $scheduledAt) { batch_id recipients { phone valid } total_valid total_invalid estimated_time } }",
    "variables": {
      "contactIds": [],
      "groupIds": [],
      "segmentIds": [],
      "phoneNumbers": ["+22507000001"],
      "templateId": "3",
      "parameters": {
        "components": [
          {
            "type": "header",
            "parameters": [
              {
                "type": "image",
                "image": {
                  "link": "https://example.com/test-image.jpg"
                }
              }
            ]
          }
        ]
      },
      "priority": 5
    }
  }')

echo "Prepare response:"
echo "$PREPARE_RESPONSE"