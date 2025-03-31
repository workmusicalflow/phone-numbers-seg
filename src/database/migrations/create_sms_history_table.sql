-- Migration pour créer la table sms_history
CREATE TABLE IF NOT EXISTS sms_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone_number_id INTEGER NULL,
    phone_number TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT NOT NULL,
    message_id TEXT NULL,
    error_message TEXT NULL,
    sender_address TEXT NOT NULL,
    sender_name TEXT NOT NULL,
    segment_id INTEGER NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phone_number_id) REFERENCES phone_numbers(id) ON DELETE SET NULL,
    FOREIGN KEY (segment_id) REFERENCES custom_segments(id) ON DELETE SET NULL
);

-- Index pour accélérer les recherches par numéro de téléphone
CREATE INDEX IF NOT EXISTS idx_sms_history_phone_number ON sms_history(phone_number);

-- Index pour accélérer les recherches par statut
CREATE INDEX IF NOT EXISTS idx_sms_history_status ON sms_history(status);

-- Index pour accélérer les recherches par date de création
CREATE INDEX IF NOT EXISTS idx_sms_history_created_at ON sms_history(created_at);

-- Index pour accélérer les recherches par ID de numéro de téléphone
CREATE INDEX IF NOT EXISTS idx_sms_history_phone_number_id ON sms_history(phone_number_id);

-- Index pour accélérer les recherches par ID de segment
CREATE INDEX IF NOT EXISTS idx_sms_history_segment_id ON sms_history(segment_id);
