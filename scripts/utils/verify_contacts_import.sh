#!/bin/bash

# Script pour vérifier que les contacts ont bien été importés pour l'utilisateur AfricaQSHE
# Ce script interroge directement la base de données SQLite

# Chemin vers la base de données SQLite
DB_PATH="src/database/database.sqlite"

# ID de l'utilisateur AfricaQSHE
USER_ID=2

# Vérifier si la base de données existe
if [ ! -f "$DB_PATH" ]; then
    echo "Erreur: La base de données n'existe pas: $DB_PATH"
    exit 1
fi

echo "Vérification des contacts pour l'utilisateur AfricaQSHE (ID: $USER_ID)..."
echo ""

# Requête SQL pour obtenir le nombre total de contacts pour cet utilisateur
TOTAL_CONTACTS=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM contacts WHERE user_id = $USER_ID;")

echo "Nombre total de contacts pour l'utilisateur AfricaQSHE: $TOTAL_CONTACTS"
echo ""

# Requête SQL pour obtenir les détails des contacts
echo "Liste des contacts:"
echo "==================="
sqlite3 "$DB_PATH" "SELECT id, name, phone_number, notes, created_at FROM contacts WHERE user_id = $USER_ID ORDER BY id DESC LIMIT 10;" -header -column

echo ""
echo "Note: Seuls les 10 contacts les plus récents sont affichés."
echo ""

# Vérifier si les numéros spécifiques du fichier CSV sont présents
echo "Vérification des numéros spécifiques du fichier CSV:"
echo "==================================================="

# Numéros de téléphone extraits du fichier CSV
PHONE_NUMBERS=(
  "+2250777104936"  # CVPA
  "+2250788548900"  # GYM CENTER MINIKAN
)

for number in "${PHONE_NUMBERS[@]}"; do
    # Nettoyer le numéro (supprimer les espaces)
    clean_number=$(echo "$number" | tr -d ' ')
    
    # Vérifier si le numéro existe dans la base de données
    contact_exists=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM contacts WHERE user_id = $USER_ID AND phone_number = '$clean_number';")
    
    if [ "$contact_exists" -gt 0 ]; then
        echo "✅ Le numéro $number est présent dans la base de données."
        
        # Afficher les détails du contact
        sqlite3 "$DB_PATH" "SELECT id, name, phone_number, notes FROM contacts WHERE user_id = $USER_ID AND phone_number = '$clean_number';" -header -column
        echo ""
    else
        echo "❌ Le numéro $number n'est PAS présent dans la base de données."
    fi
done

echo ""
echo "Vérification terminée."
