-- Migration pour créer la table whatsapp_messages

CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message_id TEXT UNIQUE NOT NULL,
    sender TEXT NOT NULL,
    recipient TEXT,
    timestamp INTEGER NOT NULL,
    type TEXT NOT NULL,
    content TEXT,
    raw_data TEXT NOT NULL,
    media_url TEXT,
    media_type TEXT,
    status TEXT,
    created_at INTEGER NOT NULL
);

-- Index pour recherche par expéditeur
CREATE INDEX IF NOT EXISTS idx_whatsapp_sender ON whatsapp_messages(sender);

-- Index pour recherche par destinataire
CREATE INDEX IF NOT EXISTS idx_whatsapp_recipient ON whatsapp_messages(recipient);

-- Index pour recherche par timestamp
CREATE INDEX IF NOT EXISTS idx_whatsapp_timestamp ON whatsapp_messages(timestamp);

-- Index pour recherche par type
CREATE INDEX IF NOT EXISTS idx_whatsapp_type ON whatsapp_messages(type);

-- Index pour recherche par statut
CREATE INDEX IF NOT EXISTS idx_whatsapp_status ON whatsapp_messages(status);