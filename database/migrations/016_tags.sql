-- ============================================================================
-- Intakely CRM - Migration 016: Tags
-- ============================================================================
-- Tenant-scoped tag definitions with colors.
-- Tags are stored as JSONB arrays on leads for fast filtering.
-- ============================================================================

CREATE TABLE IF NOT EXISTS tags (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_tags_tenant_name UNIQUE (tenant_id, name)
);

-- Row Level Security
ALTER TABLE tags ENABLE ROW LEVEL SECURITY;
ALTER TABLE tags FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON tags
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON tags TO intakely_app;
GRANT SELECT ON tags TO intakely_readonly;
