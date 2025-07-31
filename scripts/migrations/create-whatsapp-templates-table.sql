-- Création de la table whatsapp_templates
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    language VARCHAR(10) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'UTILITY',
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    components TEXT,
    is_active BOOLEAN DEFAULT 1,
    meta_template_id VARCHAR(255),
    body_text TEXT,
    header_format VARCHAR(20) DEFAULT 'NONE',
    header_text TEXT,
    footer_text TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_status ON whatsapp_templates(status);
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_language ON whatsapp_templates(language);
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_category ON whatsapp_templates(category);
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_is_active ON whatsapp_templates(is_active);
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_name ON whatsapp_templates(name);