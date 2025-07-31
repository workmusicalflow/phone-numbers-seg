-- Drop the existing table if it exists
DROP TABLE IF EXISTS sms_history;

-- Create the table with the correct schema
CREATE TABLE sms_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    phone_number_id INTEGER DEFAULT NULL,
    phone_number VARCHAR(255) NOT NULL,
    message CLOB NOT NULL,
    status VARCHAR(50) NOT NULL,
    message_id VARCHAR(255) DEFAULT NULL,
    error_message CLOB DEFAULT NULL,
    sender_address VARCHAR(255) NOT NULL,
    sender_name VARCHAR(255) NOT NULL,
    segment_id INTEGER DEFAULT NULL,
    user_id INTEGER DEFAULT NULL,
    created_at DATETIME NOT NULL
);
