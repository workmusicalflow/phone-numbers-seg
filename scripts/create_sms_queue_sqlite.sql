-- SQLite version
CREATE TABLE IF NOT EXISTS "sms_queue" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "phone_number" TEXT NOT NULL,
  "message" TEXT NOT NULL,
  "user_id" INTEGER,
  "segment_id" INTEGER,
  "status" TEXT NOT NULL DEFAULT 'PENDING',
  "created_at" TEXT NOT NULL,
  "last_attempt_at" TEXT,
  "next_attempt_at" TEXT,
  "attempts" INTEGER NOT NULL DEFAULT 0,
  "priority" INTEGER NOT NULL DEFAULT 5,
  "error_message" TEXT,
  "message_id" TEXT,
  "sender_name" TEXT,
  "sender_address" TEXT,
  "batch_id" TEXT
);

CREATE INDEX IF NOT EXISTS "idx_sms_queue_status" ON "sms_queue" ("status");
CREATE INDEX IF NOT EXISTS "idx_sms_queue_next_attempt" ON "sms_queue" ("next_attempt_at");
CREATE INDEX IF NOT EXISTS "idx_sms_queue_user_id" ON "sms_queue" ("user_id");
CREATE INDEX IF NOT EXISTS "idx_sms_queue_segment_id" ON "sms_queue" ("segment_id");
CREATE INDEX IF NOT EXISTS "idx_sms_queue_batch_id" ON "sms_queue" ("batch_id");