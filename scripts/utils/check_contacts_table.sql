-- Script SQL pour vérifier la présence des contacts dans la table contacts
-- Pour exécuter ce script:
-- sqlite3 src/database/database.sqlite < scripts/utils/check_contacts_table.sql

-- Vérifier si la table contacts existe
.print "\n=== Vérification de l'existence de la table contacts ===\n"
.tables

-- Afficher la structure de la table contacts
.print "\n=== Structure de la table contacts ===\n"
.schema contacts

-- Compter le nombre total de contacts dans la table
.print "\n=== Nombre total de contacts dans la table ===\n"
SELECT COUNT(*) AS "Nombre total de contacts" FROM contacts;

-- Compter le nombre de contacts par utilisateur
.print "\n=== Nombre de contacts par utilisateur ===\n"
.headers on
.mode column
SELECT user_id AS "ID Utilisateur", COUNT(*) AS "Nombre de contacts" 
FROM contacts 
GROUP BY user_id;

-- Afficher les informations sur l'utilisateur AfricaQSHE
.print "\n=== Informations sur l'utilisateur AfricaQSHE ===\n"
SELECT id, username, email, sms_credit, is_admin 
FROM users 
WHERE username = 'AfricaQSHE';

-- Vérifier les contacts de l'utilisateur AfricaQSHE (ID: 2)
.print "\n=== Contacts de l'utilisateur AfricaQSHE (ID: 2) ===\n"
SELECT id, name, phone_number, email, notes, created_at 
FROM contacts 
WHERE user_id = 2 
ORDER BY id DESC 
LIMIT 10;

-- Vérifier si les numéros spécifiques du fichier CSV sont présents
.print "\n=== Vérification des numéros spécifiques du fichier CSV ===\n"

-- CVPA: +2250777104936
.print "\nRecherche du numéro +2250777104936 (CVPA):"
SELECT id, user_id, name, phone_number, notes 
FROM contacts 
WHERE phone_number = '+2250777104936';

-- GYM CENTER MINIKAN: +2250788548900
.print "\nRecherche du numéro +2250788548900 (GYM CENTER MINIKAN):"
SELECT id, user_id, name, phone_number, notes 
FROM contacts 
WHERE phone_number = '+2250788548900';

-- Vérifier les permissions d'accès aux contacts dans le système
.print "\n=== Vérification des routes et permissions dans le système ===\n"
.tables routes
.tables permissions
.tables user_permissions

-- Si ces tables existent, exécuter les requêtes suivantes
.print "\nTentative de vérification des permissions pour la route 'contacts':"
SELECT * FROM routes WHERE path LIKE '%contacts%' OR name LIKE '%contacts%';
SELECT * FROM permissions WHERE name LIKE '%contact%';
SELECT * FROM user_permissions WHERE user_id = 2;

.print "\n=== Fin de la vérification ===\n"
