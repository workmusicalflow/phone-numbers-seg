-- Create the sms_queue table
CREATE TABLE `sms_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `segment_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  `next_attempt_at` datetime DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 5,
  `error_message` text DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `sender_address` varchar(255) DEFAULT NULL,
  `batch_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sms_queue_status` (`status`),
  KEY `idx_sms_queue_next_attempt` (`next_attempt_at`),
  KEY `idx_sms_queue_user_id` (`user_id`),
  KEY `idx_sms_queue_segment_id` (`segment_id`),
  KEY `idx_sms_queue_batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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