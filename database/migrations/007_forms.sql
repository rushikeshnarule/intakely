-- ============================================================================
-- Intakely CRM - Migration 007: Forms & Form Submissions
-- ============================================================================
-- Embeddable form builder with multi-step support, rate limiting,
-- honeypot spam protection, and UTM tracking on submissions.
-- ============================================================================

CREATE TABLE IF NOT EXISTS forms (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    fields JSONB NOT NULL,
    settings JSONB NOT NULL DEFAULT '{}'::jsonb,
    style JSONB NOT NULL DEFAULT '{}'::jsonb,
    is_active BOOLEAN NOT NULL DEFAULT true,
    submissions_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_forms_tenant_slug UNIQUE (tenant_id, slug)
);

-- settings JSONB expected keys:
-- multi_step, success_message, redirect_url, notification_emails,
-- pipeline_id, honeypot_enabled, rate_limit

-- Row Level Security
ALTER TABLE forms ENABLE ROW LEVEL SECURITY;
ALTER TABLE forms FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON forms
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS form_submissions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    form_id UUID NOT NULL REFERENCES forms(id),
    lead_id UUID REFERENCES leads(id),
    data JSONB NOT NULL,
    ip_address INET,
    user_agent TEXT,
    referrer VARCHAR(500),
    utm_data JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_form_submissions_form ON form_submissions (form_id, created_at DESC);

-- Row Level Security
ALTER TABLE form_submissions ENABLE ROW LEVEL SECURITY;
ALTER TABLE form_submissions FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON form_submissions
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON forms TO intakely_app;
GRANT SELECT ON forms TO intakely_readonly;
GRANT SELECT, INSERT, UPDATE, DELETE ON form_submissions TO intakely_app;
GRANT SELECT ON form_submissions TO intakely_readonly;
