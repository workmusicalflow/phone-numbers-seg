-- Phone Numbers Segmentation Web Application
-- Database Migration: Add civility and firstName fields to phone_numbers table

-- Add civility column to phone_numbers table
ALTER TABLE phone_numbers ADD COLUMN civility TEXT;

-- Add firstName column to phone_numbers table
ALTER TABLE phone_numbers ADD COLUMN firstName TEXT;

-- Update existing indexes
CREATE INDEX IF NOT EXISTS idx_phone_numbers_civility ON phone_numbers(civility);
CREATE INDEX IF NOT EXISTS idx_phone_numbers_firstName ON phone_numbers(firstName);
