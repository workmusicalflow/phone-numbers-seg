-- Suppression des tables existantes
DROP TABLE IF EXISTS whatsapp_user_templates;
DROP TABLE IF EXISTS whatsapp_templates;

-- Création de la nouvelle table unifiée whatsapp_templates
CREATE TABLE whatsapp_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    
    -- Identification du template
    name VARCHAR(255) NOT NULL,
    language VARCHAR(10) NOT NULL,
    
    -- Association utilisateur (NULL pour les templates globaux)
    user_id INTEGER DEFAULT NULL,
    
    -- Données Meta
    meta_template_id VARCHAR(255) DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'UTILITY',
    status VARCHAR(20) DEFAULT 'PENDING',
    
    -- Contenu du template
    header_format VARCHAR(20) DEFAULT 'NONE',
    header_text TEXT,
    body_text TEXT NOT NULL,
    footer_text TEXT,
    
    -- Variables et composants
    body_variables_count INTEGER DEFAULT 0,
    has_header_media BOOLEAN DEFAULT 0,
    buttons_json TEXT, -- JSON pour stocker les boutons
    
    -- Métadonnées
    is_active BOOLEAN DEFAULT 1,
    is_global BOOLEAN DEFAULT 0, -- Templates disponibles pour tous
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    
    CONSTRAINT FK_WHATSAPP_TEMPLATE_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    UNIQUE(name, language, user_id)
);

-- Index pour les requêtes fréquentes
CREATE INDEX idx_whatsapp_templates_user_id ON whatsapp_templates (user_id);
CREATE INDEX idx_whatsapp_templates_name_language ON whatsapp_templates (name, language);
CREATE INDEX idx_whatsapp_templates_status ON whatsapp_templates (status);
CREATE INDEX idx_whatsapp_templates_is_global ON whatsapp_templates (is_global);