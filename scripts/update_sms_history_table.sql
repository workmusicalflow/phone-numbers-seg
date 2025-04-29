-- Add sent_at, delivered_at, failed_at columns to sms_history table
ALTER TABLE sms_history ADD COLUMN sent_at DATETIME DEFAULT NULL;
ALTER TABLE sms_history ADD COLUMN delivered_at DATETIME DEFAULT NULL;
ALTER TABLE sms_history ADD COLUMN failed_at DATETIME DEFAULT NULL;