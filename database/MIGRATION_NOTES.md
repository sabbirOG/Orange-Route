# Database Migration Notes

## Category-Based Route System (Latest - Nov 2025)

The system has been updated to use a **category-based route system** instead of shuttles. Routes are now categorized as either **Long Route** or **Short Route**.

### For Existing Databases

Run the migration file: `migrate_to_categories.sql`

```bash
mysql -u your_user -p your_database < database/migrate_to_categories.sql
```

### Key Changes

**Removed:**
- `shuttles` table (no longer needed)
- `shuttle_assignments` table → replaced with `route_assignments`
- `shuttle_locations` table → replaced with `route_locations`
- Shuttle name, registration number, capacity fields

**Added:**
- `category` field to `routes` table (ENUM: 'long', 'short')
- `route_assignments` table (driver_id + route_id only)
- `route_locations` table (tracks driver location on routes)

**New Route Categories:**
1. **Long Route** - For longer distance shuttle services
2. **Short Route** - For shorter distance shuttle services

### Admin Workflow

1. Create routes and assign them a category (long or short)
2. Assign drivers directly to routes (no shuttle selection needed)
3. Drivers see their assigned route with category badge
4. Students see routes grouped by category with live tracking

### Assignment Flow

**Before (Shuttle-based):**
- Admin assigns: Driver + Shuttle + Route

**After (Category-based):**
- Admin assigns: Driver + Route (category is part of route)

---

## Student ID Authentication System

The system has been updated to use Student ID-based authentication instead of email verification.

### For Existing Databases

If you have an existing OrangeRoute installation, run these SQL commands to update your database:

```sql
-- 1. Make email nullable (it's now auto-generated internally)
ALTER TABLE users MODIFY email VARCHAR(255) NULL;

-- 2. Remove email UNIQUE constraint if it exists
ALTER TABLE users DROP INDEX email;
ALTER TABLE users ADD INDEX idx_email (email);

-- 3. Add UNIQUE constraint to student_id
ALTER TABLE users ADD UNIQUE INDEX idx_student_id (student_id);

-- 4. Remove email verification columns (no longer needed)
ALTER TABLE users 
  DROP COLUMN email_verified,
  DROP COLUMN verification_token,
  DROP COLUMN verification_expires_at;
```

### For Fresh Installations

Just run the updated `schema.sql` file which includes all these changes.

### Changes Summary

**Removed:**
- Email verification system (email_verified, verification_token, verification_expires_at)
- Email uniqueness constraint
- Email validation on signup/login

**Updated:**
- Email is now nullable and auto-generated as `{student_id}@student.orangeroute.local`
- Student ID is now the primary login identifier
- Auto-login after successful signup
- All roles (student, driver, admin) use Student ID for authentication

**Authentication Flow:**
1. User signs up with Student ID (9-10 digits) + password
2. System auto-generates internal email
3. User is automatically logged in and redirected to map
4. Subsequent logins use Student ID + password

### Error Handling

- Duplicate Student ID: "Account already exists with this Student ID"
- Invalid format: "Invalid student ID format. Must be 9 or 10 digits."
