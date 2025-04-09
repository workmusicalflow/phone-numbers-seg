-- Migration pour créer la table admin_action_logs (version SQLite)
-- Cette table stocke les journaux des actions effectuées par les administrateurs

-- Créer la table admin_action_logs
CREATE TABLE IF NOT EXISTS admin_action_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_id INTEGER,
    target_type VARCHAR(50),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Créer un index sur admin_id pour accélérer les recherches par administrateur
CREATE INDEX IF NOT EXISTS idx_admin_action_logs_admin_id ON admin_action_logs(admin_id);

-- Créer un index sur action_type pour accélérer les recherches par type d'action
CREATE INDEX IF NOT EXISTS idx_admin_action_logs_action_type ON admin_action_logs(action_type);

-- Créer un index sur target_id et target_type pour accélérer les recherches par cible
CREATE INDEX IF NOT EXISTS idx_admin_action_logs_target ON admin_action_logs(target_id, target_type);

-- Créer un index sur created_at pour accélérer les recherches par date
CREATE INDEX IF NOT EXISTS idx_admin_action_logs_created_at ON admin_action_logs(created_at);
