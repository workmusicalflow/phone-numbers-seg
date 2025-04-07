-- Migration pour ajouter le champ is_admin à la table users (SQLite)
-- SQLite ne supporte pas ALTER TABLE ADD COLUMN avec DEFAULT
-- Nous devons donc utiliser une approche différente

-- 1. Créer une table temporaire
CREATE TABLE users_temp (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email TEXT UNIQUE,
    sms_credit INTEGER DEFAULT 10,
    sms_limit INTEGER NULL,
    is_admin INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Copier les données de l'ancienne table vers la nouvelle
INSERT INTO users_temp (id, username, password, email, sms_credit, sms_limit, created_at, updated_at)
SELECT id, username, password, email, sms_credit, sms_limit, created_at, updated_at FROM users;

-- 3. Supprimer l'ancienne table
DROP TABLE users;

-- 4. Renommer la nouvelle table
ALTER TABLE users_temp RENAME TO users;

-- 5. Recréer les index
CREATE INDEX idx_users_username ON users(username);
