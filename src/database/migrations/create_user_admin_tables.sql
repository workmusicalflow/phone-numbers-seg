-- Migration to create tables for user management, admin features, and related entities

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Store hashed passwords
    email VARCHAR(255) UNIQUE,
    sms_credit INT DEFAULT 10, -- Initial credit of 10 free SMS
    sms_limit INT NULL, -- Optional SMS limit per user
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sender names table
CREATE TABLE IF NOT EXISTS sender_names (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(11) NOT NULL, -- Sender name max length is typically 11 chars
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS orders table
CREATE TABLE IF NOT EXISTS sms_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quantity INT NOT NULL, -- Number of SMS credits ordered
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orange API configurations table
CREATE TABLE IF NOT EXISTS orange_api_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- Can be NULL for the global admin config
    client_id VARCHAR(255) NOT NULL,
    client_secret VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE, -- Flag for admin's own config
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL -- Set NULL if user is deleted
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin contacts table (assuming admin contacts are separate from user phone numbers)
-- If admin contacts are just regular phone numbers, this might not be needed or could reference phone_numbers table
CREATE TABLE IF NOT EXISTS admin_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NULL, -- Link to custom segments if needed
    phone_number VARCHAR(20) NOT NULL UNIQUE, -- Ensure phone numbers are unique for admin contacts
    name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES custom_segments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_sender_names_user_id ON sender_names(user_id);
CREATE INDEX idx_sms_orders_user_id ON sms_orders(user_id);
CREATE INDEX idx_orange_api_configs_user_id ON orange_api_configs(user_id);
CREATE INDEX idx_admin_contacts_phone_number ON admin_contacts(phone_number);
