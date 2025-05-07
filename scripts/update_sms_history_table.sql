-- Update the sms_history table to add the batch_id column
ALTER TABLE sms_history ADD COLUMN batch_id VARCHAR(255) DEFAULT NULL;