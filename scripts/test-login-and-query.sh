#!/bin/bash

# Set the API endpoint
API_ENDPOINT="http://localhost:8000/graphql.php"

# Create a login request
LOGIN_QUERY='{
  "query": "mutation Login($username: String!, $password: String!) { login(username: $username, password: $password) }",
  "variables": {
    "username": "Admin",
    "password": "admin123"
  }
}'

# Make the login request to get a session cookie
echo "Logging in..."
COOKIE_JAR="cookie.txt"
curl -c $COOKIE_JAR -X POST $API_ENDPOINT \
  -H "Content-Type: application/json" \
  -d "$LOGIN_QUERY"

echo -e "\n\nQuerying SMS History..."
# Now make an authenticated query using the session cookie
SMS_HISTORY_QUERY='{
  "query": "{ smsHistory(limit: 5) { id, phoneNumber, message, status, createdAt, sentAt, deliveredAt, failedAt } }"
}'

curl -b $COOKIE_JAR -X POST $API_ENDPOINT \
  -H "Content-Type: application/json" \
  -d "$SMS_HISTORY_QUERY"

echo -e "\n"