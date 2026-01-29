-- PostgreSQL Optimization Script
-- This script runs on database initialization

-- Enable extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm"; -- For better text search

-- Try to enable pg_stat_statements (requires shared_preload_libraries)
DO $$
BEGIN
    CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'pg_stat_statements extension not available - requires shared_preload_libraries';
END
$$;

-- Create function to create databases for tenants dynamically
CREATE OR REPLACE FUNCTION create_tenant_database(db_name TEXT)
RETURNS VOID AS $$
BEGIN
    EXECUTE format('CREATE DATABASE %I', db_name);
EXCEPTION
    WHEN duplicate_database THEN
        -- Database already exists, do nothing
        RAISE NOTICE 'Database % already exists', db_name;
END;
$$ LANGUAGE plpgsql;

-- Create monitoring view for connection stats
CREATE OR REPLACE VIEW connection_stats AS
SELECT
    datname,
    count(*) as total_connections,
    count(*) FILTER (WHERE state = 'active') as active,
    count(*) FILTER (WHERE state = 'idle') as idle,
    count(*) FILTER (WHERE state = 'idle in transaction') as idle_in_transaction,
    count(*) FILTER (WHERE wait_event_type IS NOT NULL) as waiting
FROM pg_stat_activity
WHERE datname IS NOT NULL
GROUP BY datname;

-- Create function to get database sizes
CREATE OR REPLACE FUNCTION get_database_sizes()
RETURNS TABLE (
    database_name TEXT,
    size_mb NUMERIC
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        datname::TEXT as database_name,
        ROUND(pg_database_size(datname) / 1024.0 / 1024.0, 2) as size_mb
    FROM pg_database
    WHERE datname NOT IN ('template0', 'template1', 'postgres')
    ORDER BY pg_database_size(datname) DESC;
END;
$$ LANGUAGE plpgsql;

-- Create function to kill idle transactions
CREATE OR REPLACE FUNCTION kill_idle_transactions(idle_minutes INTEGER DEFAULT 5)
RETURNS TABLE (
    killed_pid INTEGER,
    killed_database TEXT,
    killed_user TEXT,
    idle_duration INTERVAL
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        pg_terminate_backend(pid)::INTEGER as killed_pid,
        datname::TEXT as killed_database,
        usename::TEXT as killed_user,
        now() - query_start as idle_duration
    FROM pg_stat_activity
    WHERE
        state = 'idle in transaction'
        AND (now() - query_start) > (idle_minutes || ' minutes')::INTERVAL
        AND pid != pg_backend_pid();
END;
$$ LANGUAGE plpgsql;

-- Reset pg_stat_statements if available
DO $$
BEGIN
    PERFORM pg_stat_statements_reset();
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'pg_stat_statements not available';
END
$$;

-- Grant necessary permissions
GRANT EXECUTE ON FUNCTION create_tenant_database TO postgres;
GRANT EXECUTE ON FUNCTION get_database_sizes TO postgres;
GRANT EXECUTE ON FUNCTION kill_idle_transactions TO postgres;
GRANT SELECT ON connection_stats TO postgres;
