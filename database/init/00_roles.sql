-- Create application roles with least-privilege
-- This runs as the postgres superuser during container init

-- App role: used by the PHP application. RLS is enforced (non-owner, non-superuser).
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'intakely_app') THEN
        CREATE ROLE intakely_app WITH LOGIN PASSWORD 'intakely_secret' NOSUPERUSER NOCREATEDB NOCREATEROLE;
    END IF;
END
$$;

-- Readonly reporting role
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'intakely_readonly') THEN
        CREATE ROLE intakely_readonly WITH LOGIN PASSWORD 'intakely_readonly_secret' NOSUPERUSER NOCREATEDB NOCREATEROLE;
    END IF;
END
$$;

-- Grant connect
GRANT CONNECT ON DATABASE intakely TO intakely_app;
GRANT CONNECT ON DATABASE intakely TO intakely_readonly;

-- Extensions (must be done by superuser/owner)
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "citext";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Grant schema usage
GRANT USAGE ON SCHEMA public TO intakely_app;
GRANT USAGE ON SCHEMA public TO intakely_readonly;

-- Default privileges: owner grants to app role on new tables
ALTER DEFAULT PRIVILEGES FOR ROLE intakely_owner IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO intakely_app;

ALTER DEFAULT PRIVILEGES FOR ROLE intakely_owner IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO intakely_app;

-- Readonly gets SELECT only
ALTER DEFAULT PRIVILEGES FOR ROLE intakely_owner IN SCHEMA public
    GRANT SELECT ON TABLES TO intakely_readonly;
