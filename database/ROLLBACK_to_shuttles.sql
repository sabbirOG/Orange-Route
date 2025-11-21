-- EMERGENCY ROLLBACK: Restore shuttle-based system
-- Use this ONLY if you need to go back to the original shuttle system

-- This script will restore the original shuttle tables
-- Run this if the category system is causing issues

-- Recreate shuttle tables
CREATE TABLE IF NOT EXISTS shuttles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shuttle_name VARCHAR(100) NOT NULL,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    capacity INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shuttle_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shuttle_id INT UNSIGNED NOT NULL,
    driver_id INT UNSIGNED NOT NULL,
    route_id INT UNSIGNED NOT NULL,
    is_current BOOLEAN DEFAULT TRUE,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME,
    INDEX idx_shuttle (shuttle_id),
    INDEX idx_driver (driver_id),
    INDEX idx_route (route_id),
    FOREIGN KEY (shuttle_id) REFERENCES shuttles(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shuttle_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shuttle_id INT UNSIGNED NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    speed DECIMAL(5, 2),
    heading DECIMAL(5, 2),
    accuracy DECIMAL(6, 2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_shuttle (shuttle_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (shuttle_id) REFERENCES shuttles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate data back from route_assignments to shuttle_assignments if exists
-- (This requires manual intervention as we don't have shuttle_id anymore)

-- Remove category column from routes
ALTER TABLE routes DROP COLUMN IF EXISTS category;
