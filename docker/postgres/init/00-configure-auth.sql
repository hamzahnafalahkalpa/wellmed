-- Configure PostgreSQL to use MD5 authentication instead of SCRAM-SHA-256
-- This is required for PgBouncer compatibility

ALTER SYSTEM SET password_encryption = 'md5';
SELECT pg_reload_conf();

-- Update postgres user password to use MD5
ALTER USER postgres WITH PASSWORD 'Password@123';
