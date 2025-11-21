-- Migration: Convert Shuttle-based System to Category-based Route System
-- This migration removes the shuttle concept and uses route categories (long/short) instead

-- Step 1: Create new tables
CREATE TABLE IF NOT EXISTS route_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id INT UNSIGNED NOT NULL,
    route_id INT UNSIGNED NOT NULL,
    is_current BOOLEAN DEFAULT TRUE,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME,
    INDEX idx_driver (driver_id),
    INDEX idx_route (route_id),
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS route_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    route_id INT UNSIGNED NOT NULL,
    driver_id INT UNSIGNED NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    speed DECIMAL(5, 2),
    heading DECIMAL(5, 2),
    accuracy DECIMAL(6, 2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_route (route_id),
    INDEX idx_driver (driver_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Add category column to routes table
ALTER TABLE routes ADD COLUMN IF NOT EXISTS category ENUM('long', 'short') NOT NULL DEFAULT 'short' AFTER route_name;

-- Step 3: Migrate data from shuttle_assignments to route_assignments (if tables exist)
-- This will copy only route assignments, ignoring shuttle information
INSERT INTO route_assignments (driver_id, route_id, is_current, assigned_at, ended_at)
SELECT driver_id, route_id, is_current, assigned_at, ended_at
FROM shuttle_assignments
WHERE NOT EXISTS (SELECT 1 FROM route_assignments)
ON DUPLICATE KEY UPDATE is_current = is_current;

-- Step 4: Drop old shuttle-related tables (CAUTION: This will delete all shuttle data)
-- Uncomment these lines only when you're sure you want to remove shuttle tables
-- DROP TABLE IF EXISTS shuttle_locations;
-- DROP TABLE IF EXISTS shuttle_assignments;
-- DROP TABLE IF EXISTS shuttles;

-- Note: To preserve your data, keep the old tables for a transition period.
-- Once you've verified everything works with the new system, then drop the old tables.
