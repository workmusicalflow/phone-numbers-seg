-- Phone Numbers Segmentation Web Application
-- Database Migration: Create Tables

-- Create phone_numbers table
CREATE TABLE IF NOT EXISTS phone_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number TEXT NOT NULL UNIQUE,
    name TEXT,
    company TEXT,
    sector TEXT,
    notes TEXT,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create technical_segments table for technical information
CREATE TABLE IF NOT EXISTS technical_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone_number_id INTEGER NOT NULL,
    segment_type TEXT NOT NULL,
    value TEXT NOT NULL,
    FOREIGN KEY (phone_number_id) REFERENCES phone_numbers(id) ON DELETE CASCADE
);

-- Create custom_segments table for business segmentation
CREATE TABLE IF NOT EXISTS custom_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);

-- Create phone_number_segments table for many-to-many relationship
CREATE TABLE IF NOT EXISTS phone_number_segments (
    phone_number_id INTEGER NOT NULL,
    custom_segment_id INTEGER NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (phone_number_id, custom_segment_id),
    FOREIGN KEY (phone_number_id) REFERENCES phone_numbers(id) ON DELETE CASCADE,
    FOREIGN KEY (custom_segment_id) REFERENCES custom_segments(id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_phone_numbers_number ON phone_numbers(number);
CREATE INDEX IF NOT EXISTS idx_phone_numbers_company ON phone_numbers(company);
CREATE INDEX IF NOT EXISTS idx_phone_numbers_sector ON phone_numbers(sector);
CREATE INDEX IF NOT EXISTS idx_technical_segments_phone_number_id ON technical_segments(phone_number_id);
CREATE INDEX IF NOT EXISTS idx_technical_segments_segment_type ON technical_segments(segment_type);
CREATE INDEX IF NOT EXISTS idx_phone_number_segments_phone_number_id ON phone_number_segments(phone_number_id);
CREATE INDEX IF NOT EXISTS idx_phone_number_segments_custom_segment_id ON phone_number_segments(custom_segment_id);
