-- Phone Numbers Segmentation Web Application
-- Database Migration: Create Tables

-- Create phone_numbers table
CREATE TABLE IF NOT EXISTS phone_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number TEXT NOT NULL UNIQUE,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create segments table
CREATE TABLE IF NOT EXISTS segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone_number_id INTEGER NOT NULL,
    segment_type TEXT NOT NULL,
    value TEXT NOT NULL,
    FOREIGN KEY (phone_number_id) REFERENCES phone_numbers(id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_phone_numbers_number ON phone_numbers(number);
CREATE INDEX IF NOT EXISTS idx_segments_phone_number_id ON segments(phone_number_id);
CREATE INDEX IF NOT EXISTS idx_segments_segment_type ON segments(segment_type);
