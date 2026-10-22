-- ============================================================================
-- Intakely CRM - Migration 003: Users
-- ============================================================================
-- Tenant-scoped users with RLS. Requires the citext extension.
-- custom_role_id FK will be added in 015_custom_roles.sql.
-- ============================================================================

CREATE EXTENSION IF NOT EXISTS citext;

CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    email CITEXT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'agent',
    custom_role_id UUID,  -- FK added in 015_custom_roles.sql
    permissions JSONB NOT NULL DEFAULT '{}'::jsonb,
    team VARCHAR(100),
    department VARCHAR(100),
    location VARCHAR(100),
    phone VARCHAR(50),
    avatar_url VARCHAR(500),
    timezone VARCHAR(50),
    language VARCHAR(10) DEFAULT 'en',
    is_active BOOLEAN NOT NULL DEFAULT true,
    email_verified_at TIMESTAMPTZ,
    totp_secret VARCHAR(255),
    totp_enabled BOOLEAN DEFAULT false,
    last_login_at TIMESTAMPTZ,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),

    CONSTRAINT uq_users_tenant_email UNIQUE (tenant_id, email),
    CONSTRAINT chk_users_role CHECK (role IN ('owner', 'admin', 'manager', 'agent', 'viewer'))
);

-- Row Level Security
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE users FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON users
    USING (
        NULLIF(current_setting('app.tenant_id', true), '') IS NULL
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    )
    WITH CHECK (
        NULLIF(current_setting('app.tenant_id', true), '') IS NULL
        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
    );

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON users TO intakely_app;
GRANT SELECT ON users TO intakely_readonly;
