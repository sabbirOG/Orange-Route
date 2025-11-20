-- Mark all drivers as verified
UPDATE users SET email_verified = 1 WHERE role = 'driver';
