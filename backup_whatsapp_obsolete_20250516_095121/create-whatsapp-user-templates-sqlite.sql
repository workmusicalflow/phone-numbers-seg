-- Création de la table whatsapp_user_templates
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
CREATE INDEX idx_whatsapp_user_templates_user_id ON whatsapp_user_templates(user_id);
CREATE INDEX idx_whatsapp_user_templates_template_name ON whatsapp_user_templates(template_name);