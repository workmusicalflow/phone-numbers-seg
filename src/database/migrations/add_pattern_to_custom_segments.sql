-- Add pattern field to custom_segments table
ALTER TABLE custom_segments ADD COLUMN pattern TEXT;

-- Add index for pattern field
CREATE INDEX IF NOT EXISTS idx_custom_segments_pattern ON custom_segments(pattern);
