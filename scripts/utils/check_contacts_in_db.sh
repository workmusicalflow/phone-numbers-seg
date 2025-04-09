#!/bin/bash

# Script pour vérifier la présence des contacts dans la base de données
# Ce script exécute le script SQL check_contacts_table.sql et formate la sortie

# Chemin vers la base de données SQLite
DB_PATH="src/database/database.sqlite"

# Chemin vers le script SQL
SQL_SCRIPT="scripts/utils/check_contacts_table.sql"

# Vérifier si la base de données existe
if [ ! -f "$DB_PATH" ]; then
    echo "Erreur: La base de données n'existe pas: $DB_PATH"
    exit 1
fi

# Vérifier si le script SQL existe
if [ ! -f "$SQL_SCRIPT" ]; then
    echo "Erreur: Le script SQL n'existe pas: $SQL_SCRIPT"
    exit 1
fi

echo "==================================================="
echo "  VÉRIFICATION DES CONTACTS DANS LA BASE DE DONNÉES"
echo "==================================================="
echo ""
echo "Exécution du script SQL: $SQL_SCRIPT"
echo "Base de données: $DB_PATH"
echo ""
echo "Résultats:"
echo "---------------------------------------------------"
echo ""

# Exécuter le script SQL
sqlite3 "$DB_PATH" < "$SQL_SCRIPT"

echo ""
echo "==================================================="
echo "  VÉRIFICATION TERMINÉE"
echo "==================================================="

# Vérifier si les contacts ont été trouvés pour l'utilisateur AfricaQSHE
CONTACT_COUNT=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM contacts WHERE user_id = 2;")

if [ "$CONTACT_COUNT" -gt 0 ]; then
    echo ""
    echo "✅ $CONTACT_COUNT contacts trouvés pour l'utilisateur AfricaQSHE (ID: 2)"
    echo ""
    echo "Pour envoyer un SMS à ces contacts, vous pouvez utiliser le script:"
    echo "  ./scripts/utils/send_sms_to_contacts.sh"
    echo ""
else
    echo ""
    echo "❌ Aucun contact trouvé pour l'utilisateur AfricaQSHE (ID: 2)"
    echo ""
    echo "Vérifiez que l'importation a bien été effectuée avec le script:"
    echo "  php scripts/utils/import_contacts_for_africaqshe.php"
    echo ""
fi

# Vérifier si les numéros spécifiques du fichier CSV sont présents
CVPA_COUNT=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM contacts WHERE phone_number = '+2250777104936';")
GYM_COUNT=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM contacts WHERE phone_number = '+2250788548900';")

echo "Vérification des numéros spécifiques:"
if [ "$CVPA_COUNT" -gt 0 ]; then
    echo "✅ Le numéro +2250777104936 (CVPA) est présent dans la base de données"
else
    echo "❌ Le numéro +2250777104936 (CVPA) n'est PAS présent dans la base de données"
fi

if [ "$GYM_COUNT" -gt 0 ]; then
    echo "✅ Le numéro +2250788548900 (GYM CENTER MINIKAN) est présent dans la base de données"
else
    echo "❌ Le numéro +2250788548900 (GYM CENTER MINIKAN) n'est PAS présent dans la base de données"
fi

echo ""
echo "==================================================="
