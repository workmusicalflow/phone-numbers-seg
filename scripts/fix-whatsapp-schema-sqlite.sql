-- Pour SQLite, nous devons recréer la table pour changer les contraintes
-- 1. Renommer la table existante
ALTER TABLE whatsapp_message_history RENAME TO whatsapp_message_history_old;

-- 2. Créer la nouvelle table avec wabaMessageId nullable
CREATE TABLE whatsapp_message_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    wabaMessageId VARCHAR(255) DEFAULT NULL,
    phoneNumber VARCHAR(255) NOT NULL,
    direction VARCHAR(255) NOT NULL,
    type VARCHAR(255) NOT NULL,
    content CLOB DEFAULT NULL,
    status VARCHAR(255) NOT NULL,
    timestamp DATETIME NOT NULL,
    errorCode INTEGER DEFAULT NULL,
    errorMessage CLOB DEFAULT NULL,
    conversationId VARCHAR(255) DEFAULT NULL,
    pricingCategory VARCHAR(255) DEFAULT NULL,
    mediaId VARCHAR(255) DEFAULT NULL,
    templateName VARCHAR(255) DEFAULT NULL,
    templateLanguage VARCHAR(255) DEFAULT NULL,
    contextData CLOB DEFAULT NULL,
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME DEFAULT NULL,
    oracle_user_id INTEGER NOT NULL,
    contact_id INTEGER DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    errors JSON DEFAULT NULL,
    CONSTRAINT FK_A68CBC2C0B0961D FOREIGN KEY (oracle_user_id) REFERENCES users (id),
    CONSTRAINT FK_A68CBC2E7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id)
);

-- 3. Copier les données, en mettant NULL pour les wabaMessageId vides
INSERT INTO whatsapp_message_history 
SELECT 
    id,
    CASE 
        WHEN wabaMessageId = '' THEN NULL 
        ELSE wabaMessageId 
    END as wabaMessageId,
    phoneNumber,
    direction,
    type,
    content,
    status,
    timestamp,
    errorCode,
    errorMessage,
    conversationId,
    pricingCategory,
    mediaId,
    templateName,
    templateLanguage,
    contextData,
    createdAt,
    updatedAt,
    oracle_user_id,
    contact_id,
    metadata,
    errors
FROM whatsapp_message_history_old;

-- 4. Supprimer l'ancienne table
DROP TABLE whatsapp_message_history_old;