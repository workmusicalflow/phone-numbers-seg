-- Création des tables WhatsApp

-- Table pour l'historique des messages WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_message_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oracle_user_id INTEGER NOT NULL,
    contact_id INTEGER,
    phone_number VARCHAR(20) NOT NULL,
    direction VARCHAR(10) NOT NULL,
    type VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    waba_message_id VARCHAR(255) UNIQUE,
    conversation_id VARCHAR(255),
    template_name VARCHAR(255),
    message_content TEXT,
    media_url VARCHAR(500),
    media_id VARCHAR(255),
    reaction_emoji VARCHAR(10),
    error_code VARCHAR(50),
    error_message TEXT,
    timestamp DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (oracle_user_id) REFERENCES users(id),
    FOREIGN KEY (contact_id) REFERENCES contacts(id)
);

-- Table pour les templates WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    language VARCHAR(10) NOT NULL,
    category VARCHAR(50),
    status VARCHAR(20) NOT NULL,
    components TEXT,
    is_active BOOLEAN DEFAULT 1,
    meta_template_id VARCHAR(255),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE(name, language)
);

-- Table pour la file d'attente WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    oracle_user_id INTEGER NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message_type VARCHAR(20) NOT NULL,
    payload TEXT NOT NULL,
    priority INTEGER DEFAULT 2,
    status VARCHAR(20) DEFAULT 'PENDING',
    retry_count INTEGER DEFAULT 0,
    max_retries INTEGER DEFAULT 3,
    scheduled_at DATETIME,
    processed_at DATETIME,
    error_message TEXT,
    waba_message_id VARCHAR(255),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (oracle_user_id) REFERENCES users(id)
);

-- Table de jonction pour les templates utilisateur 
-- (déjà créée précédemment mais ajoutée ici pour compléter)
CREATE TABLE IF NOT EXISTS whatsapp_user_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    language_code VARCHAR(10) NOT NULL,
    body_variables_count INTEGER DEFAULT 0,
    has_header_media BOOLEAN DEFAULT 0,
    is_special_template BOOLEAN DEFAULT 0,
    header_media_url VARCHAR(500),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, template_name, language_code)
);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_wh_message_history_user ON whatsapp_message_history(oracle_user_id);
CREATE INDEX IF NOT EXISTS idx_wh_message_history_contact ON whatsapp_message_history(contact_id);
CREATE INDEX IF NOT EXISTS idx_wh_message_history_phone ON whatsapp_message_history(phone_number);
CREATE INDEX IF NOT EXISTS idx_wh_message_history_status ON whatsapp_message_history(status);
CREATE INDEX IF NOT EXISTS idx_wh_message_history_timestamp ON whatsapp_message_history(timestamp);

CREATE INDEX IF NOT EXISTS idx_wh_queue_user ON whatsapp_queue(oracle_user_id);
CREATE INDEX IF NOT EXISTS idx_wh_queue_status ON whatsapp_queue(status);
CREATE INDEX IF NOT EXISTS idx_wh_queue_scheduled ON whatsapp_queue(scheduled_at);
CREATE INDEX IF NOT EXISTS idx_wh_queue_priority ON whatsapp_queue(priority);

CREATE INDEX IF NOT EXISTS idx_wh_templates_status ON whatsapp_templates(status);
CREATE INDEX IF NOT EXISTS idx_wh_templates_active ON whatsapp_templates(is_active);