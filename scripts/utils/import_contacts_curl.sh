#!/bin/bash

# Script pour importer les contacts du fichier CSV en utilisant curl
# Ce script envoie le fichier CSV à l'API REST pour l'importer et associer les contacts à l'utilisateur AfricaQSHE

# Chemin vers le fichier CSV
CSV_FILE="Copie de contacts.csv"

# Vérifier si le fichier existe
if [ ! -f "$CSV_FILE" ]; then
    echo "Erreur: Le fichier CSV '$CSV_FILE' n'existe pas."
    exit 1
fi

echo "Importation des contacts du fichier '$CSV_FILE' pour l'utilisateur AfricaQSHE (ID: 2)..."

# Utiliser curl pour envoyer le fichier CSV à l'API
# L'endpoint import-csv attend un fichier CSV avec le paramètre csv_file
# Nous spécifions également que la colonne 3 (index 3) contient les numéros de téléphone
# et que le fichier a une ligne d'en-tête
curl -X POST \
     -F "csv_file=@$CSV_FILE" \
     -F "has_header=true" \
     -F "delimiter=," \
     -F "phone_column=3" \
     -F "user_id=2" \
     "http://localhost:8000/api.php?endpoint=import-csv"

echo ""
echo "Importation terminée."
