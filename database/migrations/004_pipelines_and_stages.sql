-- ============================================================================
-- Intakely CRM - Migration 004: Pipelines & Pipeline Stages
-- ============================================================================
-- Visual Kanban sales pipelines with configurable stages.
-- Each tenant can have multiple pipelines (e.g. Sales, Support, Onboarding).
-- ============================================================================

CREATE TABLE IF NOT EXISTS pipelines (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    is_default BOOLEAN NOT NULL DEFAULT false,
    sort_order INT NOT NULL DEFAULT 0,
    settings JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_pipelines_tenant_slug UNIQUE (tenant_id, slug)
);

-- Row Level Security
ALTER TABLE pipelines ENABLE ROW LEVEL SECURITY;
ALTER TABLE pipelines FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON pipelines
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS pipeline_stages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    pipeline_id UUID NOT NULL REFERENCES pipelines(id),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    color VARCHAR(7),
    is_won BOOLEAN NOT NULL DEFAULT false,
    is_lost BOOLEAN NOT NULL DEFAULT false,
    required_fields JSONB NOT NULL DEFAULT '[]'::jsonb,
    wip_limit INT,
    sla_hours INT,
    auto_actions JSONB NOT NULL DEFAULT '[]'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_pipeline_stages_pipeline_slug UNIQUE (pipeline_id, slug)
);

-- Row Level Security
ALTER TABLE pipeline_stages ENABLE ROW LEVEL SECURITY;
ALTER TABLE pipeline_stages FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON pipeline_stages
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON pipelines TO intakely_app;
GRANT SELECT ON pipelines TO intakely_readonly;
GRANT SELECT, INSERT, UPDATE, DELETE ON pipeline_stages TO intakely_app;
GRANT SELECT ON pipeline_stages TO intakely_readonly;
