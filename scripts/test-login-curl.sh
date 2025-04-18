#!/bin/bash

# Test login with Admin account
echo "Testing login with Admin account..."
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"query":"mutation { login(username: \"Admin\", password: \"oraclesms2025-0\") }"}' \
  http://localhost:8000/graphql.php

echo -e "\n\n"

# Test login with AfricaQSHE account
echo "Testing login with AfricaQSHE account..."
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"query":"mutation { login(username: \"AfricaQSHE\", password: \"Qualitas@2024\") }"}' \
  http://localhost:8000/graphql.php

echo -e "\n\n"

# Test the 'me' query to see if we're authenticated
echo "Testing 'me' query with cookies..."
curl -X POST \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -c cookie.txt \
  -d '{"query":"query { me { id username email isAdmin } }"}' \
  http://localhost:8000/graphql.php
