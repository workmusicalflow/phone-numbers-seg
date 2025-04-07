-- Migration pour ajouter le champ is_admin à la table users
-- Version MySQL
ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE;

-- Version SQLite
-- SQLite ne supporte pas ALTER TABLE ADD COLUMN avec DEFAULT
-- Cette migration doit être exécutée séparément pour SQLite
