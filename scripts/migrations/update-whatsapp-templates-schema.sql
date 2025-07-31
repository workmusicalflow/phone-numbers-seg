-- Mise à jour de la table whatsapp_templates
ALTER TABLE whatsapp_templates 
ADD COLUMN IF NOT EXISTS body_text TEXT,
ADD COLUMN IF NOT EXISTS header_format VARCHAR(20) DEFAULT 'NONE',
ADD COLUMN IF NOT EXISTS header_text TEXT,
ADD COLUMN IF NOT EXISTS footer_text TEXT,
MODIFY COLUMN category VARCHAR(50) NOT NULL DEFAULT 'UTILITY';

-- S'assurer que les index existent
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_status ON whatsapp_templates(status);
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_language ON whatsapp_templates(language);
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_category ON whatsapp_templates(category);
CREATE INDEX IF NOT EXISTS idx_whatsapp_templates_is_active ON whatsapp_templates(is_active);