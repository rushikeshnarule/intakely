-- ============================================================================
-- Intakely CRM - Migration 008: Automations & Automation Logs
-- ============================================================================
-- Trigger-based workflow automations with condition groups (AND/OR)
-- and an execution log for debugging and monitoring.
-- ============================================================================

CREATE TABLE IF NOT EXISTS automations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    trigger_type VARCHAR(50) NOT NULL,
    trigger_config JSONB NOT NULL DEFAULT '{}'::jsonb,
    conditions JSONB NOT NULL DEFAULT '[]'::jsonb,
    actions JSONB NOT NULL DEFAULT '[]'::jsonb,
    is_active BOOLEAN NOT NULL DEFAULT true,
    run_count INT NOT NULL DEFAULT 0,
    last_run_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Row Level Security
ALTER TABLE automations ENABLE ROW LEVEL SECURITY;
ALTER TABLE automations FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON automations
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS automation_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    automation_id UUID NOT NULL REFERENCES automations(id),
    lead_id UUID,
    trigger_data JSONB,
    actions_executed JSONB,
    status VARCHAR(20) NOT NULL,
    error TEXT,
    duration_ms INT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_automation_logs_status CHECK (status IN ('success', 'partial', 'failed'))
);

CREATE INDEX idx_automation_logs_automation ON automation_logs (automation_id, created_at DESC);

-- Row Level Security
ALTER TABLE automation_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE automation_logs FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON automation_logs
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON automations TO intakely_app;
GRANT SELECT ON automations TO intakely_readonly;
GRANT SELECT, INSERT, UPDATE, DELETE ON automation_logs TO intakely_app;
GRANT SELECT ON automation_logs TO intakely_readonly;
