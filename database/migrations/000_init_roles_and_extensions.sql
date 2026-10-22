-- Create application roles and required extensions if they don't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'intakely_app') THEN
        CREATE ROLE intakely_app WITH LOGIN PASSWORD 'intakely_secret';
    END IF;
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'intakely_readonly') THEN
        CREATE ROLE intakely_readonly WITH LOGIN PASSWORD 'intakely_readonly_secret';
    END IF;
EXCEPTION WHEN OTHERS THEN
    -- In managed databases without superuser, role creation might be restricted
    NULL;
END
$$;

CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "citext";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
