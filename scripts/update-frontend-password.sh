#!/bin/bash

echo "Mise à jour du mot de passe admin dans le frontend..."

# Créer un fichier .env pour le frontend
cd /Users/ns2poportable/Desktop/phone-numbers-seg/frontend
echo "VITE_ADMIN_PASSWORD=admin123" >> .env.local

echo "Mot de passe mis à jour dans .env.local"
echo "Relancez le serveur frontend pour prendre en compte les changements"