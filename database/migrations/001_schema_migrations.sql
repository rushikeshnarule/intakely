-- ============================================================================
-- Intakely CRM - Migration 001: Schema Migrations Tracker
-- ============================================================================
-- Tracks which migration files have been applied to the database.
-- This table is managed by the migration runner and should not be modified.
-- ============================================================================

CREATE TABLE IF NOT EXISTS schema_migrations (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON schema_migrations TO intakely_app;
GRANT SELECT ON schema_migrations TO intakely_readonly;
GRANT USAGE, SELECT ON SEQUENCE schema_migrations_id_seq TO intakely_app;
GRANT USAGE, SELECT ON SEQUENCE schema_migrations_id_seq TO intakely_readonly;
