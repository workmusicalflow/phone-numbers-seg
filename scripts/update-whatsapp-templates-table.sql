-- Script SQL pour mettre à jour la table whatsapp_templates avec les colonnes manquantes

-- Ajouter les colonnes manquantes si elles n'existent pas
ALTER TABLE whatsapp_templates ADD COLUMN IF NOT EXISTS body_text TEXT NOT NULL DEFAULT '';
ALTER TABLE whatsapp_templates ADD COLUMN IF NOT EXISTS header_format VARCHAR(20) NOT NULL DEFAULT 'NONE';
ALTER TABLE whatsapp_templates ADD COLUMN IF NOT EXISTS header_text TEXT;
ALTER TABLE whatsapp_templates ADD COLUMN IF NOT EXISTS footer_text TEXT;
ALTER TABLE whatsapp_templates ADD COLUMN IF NOT EXISTS meta_template_id VARCHAR(255);