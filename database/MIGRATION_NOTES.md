# Database Migration Notes

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
