-- Cek dan buat database jika belum ada
DO
$$
BEGIN
   IF NOT EXISTS (
      SELECT FROM pg_catalog.pg_database WHERE datname = 'central'
   ) THEN
      CREATE DATABASE central;
   END IF;
END
$$;

-- Cek dan buat user jika belum ada
DO
$$
BEGIN
   IF NOT EXISTS (
      SELECT FROM pg_catalog.pg_roles WHERE rolname = 'laravel_user'
   ) THEN
      CREATE ROLE laravel_user LOGIN PASSWORD 'Password@123.,';
   END IF;
END
$$;

-- Grant akses ke user laravel_user
GRANT ALL PRIVILEGES ON DATABASE central TO laravel_user;

-- Pindah ke database 'central'
\c central;

-- Grant akses ke semua tabel, sequence, dan function di schema public
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO laravel_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO laravel_user;
GRANT ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public TO laravel_user;
