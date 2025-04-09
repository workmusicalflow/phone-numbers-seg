-- Script SQL pour vérifier les contacts importés pour l'utilisateur AfricaQSHE
-- Pour exécuter ce script:
-- sqlite3 src/database/database.sqlite < scripts/utils/verify_contacts.sql

-- Afficher le nombre total de contacts pour l'utilisateur AfricaQSHE (ID: 2)
.print "\n=== Nombre total de contacts pour l'utilisateur AfricaQSHE (ID: 2) ===\n"
SELECT COUNT(*) AS "Nombre de contacts" FROM contacts WHERE user_id = 2;

-- Afficher les 10 contacts les plus récents
.print "\n=== Liste des 10 contacts les plus récents ===\n"
.headers on
.mode column
SELECT id, name, phone_number, notes, created_at 
FROM contacts 
WHERE user_id = 2 
ORDER BY id DESC 
LIMIT 10;

-- Vérifier si les numéros spécifiques du fichier CSV sont présents
.print "\n=== Vérification des numéros spécifiques du fichier CSV ===\n"

-- CVPA: +2250777104936
.print "\nRecherche du numéro +2250777104936 (CVPA):"
SELECT id, name, phone_number, notes 
FROM contacts 
WHERE user_id = 2 AND phone_number = '+2250777104936';

-- GYM CENTER MINIKAN: +2250788548900
.print "\nRecherche du numéro +2250788548900 (GYM CENTER MINIKAN):"
SELECT id, name, phone_number, notes 
FROM contacts 
WHERE user_id = 2 AND phone_number = '+2250788548900';

.print "\n=== Fin de la vérification ===\n"
