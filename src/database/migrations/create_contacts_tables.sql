-- Table pour les contacts
CREATE TABLE IF NOT EXISTS contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    notes TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour accélérer les recherches par utilisateur
CREATE INDEX IF NOT EXISTS idx_contacts_user_id ON contacts(user_id);

-- Index pour accélérer les recherches par numéro de téléphone
CREATE INDEX IF NOT EXISTS idx_contacts_phone_number ON contacts(phone_number);

-- Table pour les groupes de contacts
CREATE TABLE IF NOT EXISTS contact_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour accélérer les recherches par utilisateur
CREATE INDEX IF NOT EXISTS idx_contact_groups_user_id ON contact_groups(user_id);

-- Table pour les associations entre contacts et groupes
CREATE TABLE IF NOT EXISTS contact_group_memberships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contact_id INTEGER NOT NULL,
    group_id INTEGER NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES contact_groups(id) ON DELETE CASCADE,
    UNIQUE(contact_id, group_id)
);

-- Index pour accélérer les recherches par contact
CREATE INDEX IF NOT EXISTS idx_memberships_contact_id ON contact_group_memberships(contact_id);

-- Index pour accélérer les recherches par groupe
CREATE INDEX IF NOT EXISTS idx_memberships_group_id ON contact_group_memberships(group_id);
