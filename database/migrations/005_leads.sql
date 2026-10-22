-- ============================================================================
-- Intakely CRM - Migration 005: Leads
-- ============================================================================
-- Core leads table with full-text search, SLA tracking, consent management,
-- duplicate detection indexes, and UTM attribution.
-- ============================================================================

CREATE EXTENSION IF NOT EXISTS pg_trgm;

CREATE TABLE IF NOT EXISTS leads (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    pipeline_id UUID REFERENCES pipelines(id),
    stage_id UUID REFERENCES pipeline_stages(id),
    assigned_to UUID REFERENCES users(id),
    name VARCHAR(255) NOT NULL,
    email CITEXT,
    phone VARCHAR(50),
    phone_country VARCHAR(5),
    company VARCHAR(255),
    source VARCHAR(50),
    source_detail VARCHAR(255),
    utm_source VARCHAR(255),
    utm_medium VARCHAR(255),
    utm_campaign VARCHAR(255),
    utm_content VARCHAR(255),
    utm_term VARCHAR(255),
    landing_page VARCHAR(500),
    referrer VARCHAR(500),
    service VARCHAR(255),
    services JSONB NOT NULL DEFAULT '[]'::jsonb,
    value DECIMAL(12,2),
    currency VARCHAR(3) DEFAULT 'GBP',
    score INT DEFAULT 0,
    priority VARCHAR(10) NOT NULL DEFAULT 'normal',
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    lost_reason VARCHAR(255),
    language VARCHAR(10) DEFAULT 'en',
    tags JSONB NOT NULL DEFAULT '[]'::jsonb,
    custom_fields JSONB NOT NULL DEFAULT '{}'::jsonb,
    encrypted_fields JSONB NOT NULL DEFAULT '{}'::jsonb,
    form_data JSONB NOT NULL DEFAULT '{}'::jsonb,
    consent_given BOOLEAN DEFAULT false,
    consent_at TIMESTAMPTZ,
    consent_form_version VARCHAR(50),
    do_not_contact BOOLEAN DEFAULT false,
    duplicate_of UUID REFERENCES leads(id),
    first_response_at TIMESTAMPTZ,
    sla_deadline TIMESTAMPTZ,
    sla_breached BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    search_vector TSVECTOR,

    CONSTRAINT chk_leads_score CHECK (score >= 0 AND score <= 100),
    CONSTRAINT chk_leads_priority CHECK (priority IN ('high', 'normal', 'low')),
    CONSTRAINT chk_leads_status CHECK (status IN ('open', 'won', 'lost', 'archived')),
    CONSTRAINT chk_leads_source CHECK (source IS NULL OR source IN (
        'web_form', 'callback', 'email', 'whatsapp', 'sms',
        'facebook', 'instagram', 'google_ads', 'api', 'manual', 'csv_import'
    ))
);

-- Full-text search
CREATE INDEX idx_leads_search_vector ON leads USING GIN (search_vector);

-- Tag filtering (JSONB GIN)
CREATE INDEX idx_leads_tags ON leads USING GIN (tags);

-- Custom fields filtering
CREATE INDEX idx_leads_custom_fields ON leads USING GIN (custom_fields);

-- Active leads by assignee (partial index)
CREATE INDEX idx_leads_open_assigned ON leads (tenant_id, assigned_to)
    WHERE status = 'open';

-- SLA breach monitoring (partial index)
CREATE INDEX idx_leads_sla_breached ON leads (tenant_id)
    WHERE sla_breached = true;

-- Duplicate detection: trigram on name
CREATE INDEX idx_leads_name_trgm ON leads USING GIN (name gin_trgm_ops);

-- Duplicate detection: email
CREATE INDEX idx_leads_tenant_email ON leads (tenant_id, email)
    WHERE email IS NOT NULL;

-- Duplicate detection: phone
CREATE INDEX idx_leads_tenant_phone ON leads (tenant_id, phone)
    WHERE phone IS NOT NULL;

-- Timeline / listing
CREATE INDEX idx_leads_tenant_created ON leads (tenant_id, created_at DESC);

-- Pipeline board view
CREATE INDEX idx_leads_pipeline_stage ON leads (tenant_id, pipeline_id, stage_id);

-- -------------------------------------------------------
-- Auto-update search_vector on INSERT / UPDATE
-- -------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_leads_search_vector()
RETURNS TRIGGER AS $$
BEGIN
    NEW.search_vector := to_tsvector(
        'english',
        coalesce(NEW.name, '') || ' ' ||
        coalesce(NEW.email::text, '') || ' ' ||
        coalesce(NEW.phone, '') || ' ' ||
        coalesce(NEW.company, '') || ' ' ||
        coalesce(NEW.service, '')
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_leads_search_vector
    BEFORE INSERT OR UPDATE ON leads
    FOR EACH ROW
    EXECUTE FUNCTION fn_leads_search_vector();

-- Row Level Security
ALTER TABLE leads ENABLE ROW LEVEL SECURITY;
ALTER TABLE leads FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON leads
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON leads TO intakely_app;
GRANT SELECT ON leads TO intakely_readonly;
