-- Ensure contact_groups table exists and matches Doctrine entity
CREATE TABLE IF NOT EXISTS contact_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL, -- Using TEXT for string compatibility
    description TEXT,
    created_at TEXT NOT NULL, -- Using TEXT for DATETIME compatibility in SQLite
    updated_at TEXT NOT NULL, -- Using TEXT for DATETIME compatibility in SQLite
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE -- Assuming FK constraint is desired
);

-- Add unique constraint to orange_api_configs table to ensure one config per user
CREATE UNIQUE INDEX unique_user_config ON orange_api_configs(user_id) WHERE user_id IS NOT NULL;

-- Create custom_segments table for Doctrine entity
CREATE TABLE IF NOT EXISTS custom_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, -- Using TEXT for string compatibility
    description TEXT,
    pattern TEXT
);

-- Create phone_number_segments table for Doctrine entity
CREATE TABLE IF NOT EXISTS phone_number_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone_number_id INTEGER NOT NULL,
    custom_segment_id INTEGER NOT NULL,
    created_at TEXT NOT NULL, -- Using TEXT for DATETIME compatibility in SQLite
    FOREIGN KEY (phone_number_id) REFERENCES phone_numbers(id) ON DELETE CASCADE,
    FOREIGN KEY (custom_segment_id) REFERENCES custom_segments(id) ON DELETE CASCADE
);

-- Note: We don't need to modify the sender_names table as it already has the structure we need.
-- The limit of two approved sender names per user will be enforced through application logic.
