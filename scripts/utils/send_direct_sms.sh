#!/bin/bash

# Script pour envoyer un SMS directement aux numéros spécifiés dans le fichier CSV
# Ce script utilise GraphQL pour envoyer un SMS aux numéros de téléphone

# ID de l'utilisateur AfricaQSHE
USER_ID=2

# Message à envoyer
MESSAGE="Ceci est un message de test envoyé directement aux numéros du fichier CSV."

# Numéros de téléphone extraits du fichier CSV
# Ces numéros sont directement extraits du fichier "Copie de contacts.csv"
PHONE_NUMBERS=(
  "+2250777104936"  # CVPA
  "+2250788548900"  # GYM CENTER MINIKAN
)

# Convertir les numéros en format JSON pour la requête GraphQL
PHONE_NUMBERS_JSON="["
for number in "${PHONE_NUMBERS[@]}"; do
    PHONE_NUMBERS_JSON+="\"$number\","
done
# Supprimer la dernière virgule et fermer le tableau
PHONE_NUMBERS_JSON=${PHONE_NUMBERS_JSON%,}
PHONE_NUMBERS_JSON+="]"

echo "Envoi d'un SMS à ${#PHONE_NUMBERS[@]} numéros spécifiques..."

# Construire la requête GraphQL
GRAPHQL_QUERY="{\"query\":\"mutation { sendBulkSms(phoneNumbers: $PHONE_NUMBERS_JSON, message: \\\"$MESSAGE\\\", userId: $USER_ID) { status message summary { total successful failed } results { phoneNumber status message } } }\"}"

# Envoyer la requête GraphQL
curl -X POST \
     -H "Content-Type: application/json" \
     --data "$GRAPHQL_QUERY" \
     "http://localhost:8000/graphql.php"

echo ""
echo "Opération terminée."
