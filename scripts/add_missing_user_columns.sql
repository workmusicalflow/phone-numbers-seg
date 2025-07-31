-- Add missing columns to users table
ALTER TABLE users ADD COLUMN api_key TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN reset_token TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT 0;