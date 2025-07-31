-- Création de la table pour les templates WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    language VARCHAR(10) NOT NULL,
    category VARCHAR(50) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    components TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    meta_template_id VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE(name, language)
);

-- Création de la table pour l'historique des messages WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_message_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oracle_user_id INTEGER NOT NULL,
    waba_message_id VARCHAR(255) NULL,
    phone_number VARCHAR(20) NOT NULL,
    direction VARCHAR(10) NOT NULL,
    type VARCHAR(20) NOT NULL,
    content TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'sent',
    error_code VARCHAR(20) NULL,
    error_message TEXT NULL,
    media_id VARCHAR(255) NULL,
    template_name VARCHAR(255) NULL,
    template_language VARCHAR(10) NULL,
    timestamp DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (oracle_user_id) REFERENCES users(id)
);

-- Index pour améliorer les performances des requêtes (ajouter après la création des tables)
-- CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_name ON whatsapp_templates(name);
-- CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_status ON whatsapp_templates(status);
-- CREATE INDEX IF NOT EXISTS idx_whatsapp_message_history_waba_id ON whatsapp_message_history(waba_message_id);
-- CREATE INDEX IF NOT EXISTS idx_whatsapp_message_history_phone ON whatsapp_message_history(phone_number);
-- CREATE INDEX IF NOT EXISTS idx_whatsapp_message_history_timestamp ON whatsapp_message_history(timestamp);
-- CREATE INDEX IF NOT EXISTS idx_whatsapp_message_history_user ON whatsapp_message_history(oracle_user_id);