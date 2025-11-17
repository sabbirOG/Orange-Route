-- Add missing columns to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) AFTER student_id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS session VARCHAR(20) AFTER department;
ALTER TABLE users ADD COLUMN IF NOT EXISTS language VARCHAR(10) DEFAULT 'en' AFTER profile_picture;
ALTER TABLE users ADD COLUMN IF NOT EXISTS privacy_profile VARCHAR(20) DEFAULT 'public' AFTER language;
ALTER TABLE users ADD COLUMN IF NOT EXISTS notifications_enabled BOOLEAN DEFAULT TRUE AFTER privacy_profile;
