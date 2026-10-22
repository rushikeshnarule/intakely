-- ============================================================================
-- Intakely CRM - Migration 017: Contacts & Companies
-- ============================================================================
-- B2B company records and contact persons linked to leads.
-- ============================================================================

CREATE TABLE IF NOT EXISTS companies (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(255) NOT NULL,
    website VARCHAR(500),
    industry VARCHAR(100),
    size VARCHAR(50),
    address TEXT,
    custom_fields JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_companies_tenant_name ON companies (tenant_id, name);

-- Row Level Security
ALTER TABLE companies ENABLE ROW LEVEL SECURITY;
ALTER TABLE companies FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON companies
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS contacts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    lead_id UUID REFERENCES leads(id),
    company_id UUID REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    email CITEXT,
    phone VARCHAR(50),
    role VARCHAR(100),
    is_primary BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_contacts_lead ON contacts (lead_id);
CREATE INDEX idx_contacts_company ON contacts (company_id);

-- Row Level Security
ALTER TABLE contacts ENABLE ROW LEVEL SECURITY;
ALTER TABLE contacts FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON contacts
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON companies TO intakely_app;
GRANT SELECT ON companies TO intakely_readonly;
GRANT SELECT, INSERT, UPDATE, DELETE ON contacts TO intakely_app;
GRANT SELECT ON contacts TO intakely_readonly;
