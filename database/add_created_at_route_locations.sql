-- Migration: Ensure route_locations has created_at timestamp
-- Safe to run multiple times; will only add if missing.

SET @have_created := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'route_locations'
      AND column_name = 'created_at'
);

-- Add column if missing
SET @stmt := IF(@have_created = 0,
    'ALTER TABLE route_locations ADD COLUMN created_at DATETIME NULL',
    'SELECT 1');
PREPARE addcol FROM @stmt; EXECUTE addcol; DEALLOCATE PREPARE addcol;

-- Backfill from updated_at if available and created_at is NULL
SET @have_updated := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'route_locations'
      AND column_name = 'updated_at'
);

SET @stmt := IF(@have_created = 0 AND @have_updated = 1,
    'UPDATE route_locations SET created_at = updated_at WHERE created_at IS NULL',
    'SELECT 1');
PREPARE backfill FROM @stmt; EXECUTE backfill; DEALLOCATE PREPARE backfill;

-- Make column NOT NULL with default
SET @stmt := IF(@have_created = 0,
    'ALTER TABLE route_locations MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    'SELECT 1');
PREPARE enforce FROM @stmt; EXECUTE enforce; DEALLOCATE PREPARE enforce;

-- Create index if not exists (MySQL 8.0 lacks true IF NOT EXISTS for indexes before 8.0.21)
SET @have_index := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'route_locations' AND index_name = 'idx_created'
);
SET @stmt := IF(@have_index = 0, 'CREATE INDEX idx_created ON route_locations(created_at)', 'SELECT 1');
PREPARE idxstmt FROM @stmt; EXECUTE idxstmt; DEALLOCATE PREPARE idxstmt;

-- Done
SELECT 'route_locations.created_at ensured' AS status;