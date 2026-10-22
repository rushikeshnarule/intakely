-- ============================================================================
-- Intakely CRM - Migration 015: Custom Roles
-- ============================================================================
-- Tenant-scoped custom roles with JSONB permission sets.
-- Also adds the deferred FK from users.custom_role_id.
-- ============================================================================

CREATE TABLE IF NOT EXISTS custom_roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(255) NOT NULL,
    permissions JSONB NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_custom_roles_tenant_name UNIQUE (tenant_id, name)
);

-- Row Level Security
ALTER TABLE custom_roles ENABLE ROW LEVEL SECURITY;
ALTER TABLE custom_roles FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON custom_roles
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Add deferred FK from users table
ALTER TABLE users
    ADD CONSTRAINT fk_users_custom_role
    FOREIGN KEY (custom_role_id) REFERENCES custom_roles(id);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON custom_roles TO intakely_app;
GRANT SELECT ON custom_roles TO intakely_readonly;
