-- Table pour les SMS planifiés
CREATE TABLE IF NOT EXISTS scheduled_sms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sender_name_id INTEGER NOT NULL,
    scheduled_date DATETIME NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending, sent, failed, cancelled
    is_recurring BOOLEAN NOT NULL DEFAULT 0,
    recurrence_pattern VARCHAR(50) NULL, -- daily, weekly, monthly, custom
    recurrence_config TEXT NULL, -- JSON configuration pour la récurrence
    recipients_type VARCHAR(50) NOT NULL, -- contacts, groups, segments, numbers
    recipients_data TEXT NOT NULL, -- JSON avec les IDs ou numéros
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    last_run_at DATETIME NULL,
    next_run_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_name_id) REFERENCES sender_names(id) ON DELETE CASCADE
);

-- Index pour accélérer les recherches par utilisateur
CREATE INDEX IF NOT EXISTS idx_scheduled_sms_user_id ON scheduled_sms(user_id);

-- Index pour accélérer les recherches par date planifiée
CREATE INDEX IF NOT EXISTS idx_scheduled_sms_scheduled_date ON scheduled_sms(scheduled_date);

-- Index pour accélérer les recherches par statut
CREATE INDEX IF NOT EXISTS idx_scheduled_sms_status ON scheduled_sms(status);

-- Index pour accélérer les recherches par prochaine exécution
CREATE INDEX IF NOT EXISTS idx_scheduled_sms_next_run_at ON scheduled_sms(next_run_at);

-- Table pour les logs d'exécution des SMS planifiés
CREATE TABLE IF NOT EXISTS scheduled_sms_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scheduled_sms_id INTEGER NOT NULL,
    execution_date DATETIME NOT NULL,
    status VARCHAR(50) NOT NULL, -- success, partial_success, failed
    total_recipients INTEGER NOT NULL,
    successful_sends INTEGER NOT NULL,
    failed_sends INTEGER NOT NULL,
    error_details TEXT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (scheduled_sms_id) REFERENCES scheduled_sms(id) ON DELETE CASCADE
);

-- Index pour accélérer les recherches par SMS planifié
CREATE INDEX IF NOT EXISTS idx_scheduled_sms_logs_scheduled_sms_id ON scheduled_sms_logs(scheduled_sms_id);

-- Index pour accélérer les recherches par date d'exécution
CREATE INDEX IF NOT EXISTS idx_scheduled_sms_logs_execution_date ON scheduled_sms_logs(execution_date);
