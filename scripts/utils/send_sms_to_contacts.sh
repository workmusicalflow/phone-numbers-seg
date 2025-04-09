#!/bin/bash

# Script pour envoyer un SMS aux contacts de l'utilisateur AfricaQSHE
# Ce script utilise GraphQL pour récupérer les contacts de l'utilisateur et leur envoyer un SMS

# ID de l'utilisateur AfricaQSHE
USER_ID=2

# Message à envoyer
MESSAGE="Ceci est un message de test envoyé depuis le système de gestion de SMS."

# Récupérer les numéros de téléphone des contacts de l'utilisateur
echo "Récupération des contacts de l'utilisateur AfricaQSHE (ID: $USER_ID)..."

# Utiliser curl pour récupérer les contacts via l'API REST
# Nous limitons à 100 contacts pour cet exemple
CONTACTS_RESPONSE=$(curl -s -X GET "http://localhost:8000/api.php?endpoint=contacts&user_id=$USER_ID&limit=100")

# Extraire les numéros de téléphone des contacts (cette partie est simplifiée et pourrait nécessiter un traitement JSON plus robuste)
# Dans un environnement de production, utilisez jq ou un autre outil de traitement JSON
PHONE_NUMBERS=$(echo "$CONTACTS_RESPONSE" | grep -o '"phone_number":"[^"]*"' | sed 's/"phone_number":"//g' | sed 's/"//g')

# Vérifier si des numéros ont été trouvés
if [ -z "$PHONE_NUMBERS" ]; then
    echo "Aucun contact trouvé pour l'utilisateur."
    exit 1
fi

# Convertir les numéros en format JSON pour la requête GraphQL
PHONE_NUMBERS_JSON="["
for number in $PHONE_NUMBERS; do
    PHONE_NUMBERS_JSON+="\"$number\","
done
# Supprimer la dernière virgule et fermer le tableau
PHONE_NUMBERS_JSON=${PHONE_NUMBERS_JSON%,}
PHONE_NUMBERS_JSON+="]"

echo "Envoi d'un SMS à $(echo "$PHONE_NUMBERS" | wc -l | tr -d ' ') contacts..."

# Construire la requête GraphQL
GRAPHQL_QUERY="{\"query\":\"mutation { sendBulkSms(phoneNumbers: $PHONE_NUMBERS_JSON, message: \\\"$MESSAGE\\\", userId: $USER_ID) { status message summary { total successful failed } results { phoneNumber status message } } }\"}"

# Envoyer la requête GraphQL
curl -X POST \
     -H "Content-Type: application/json" \
     --data "$GRAPHQL_QUERY" \
     "http://localhost:8000/graphql.php"

echo ""
echo "Opération terminée."
