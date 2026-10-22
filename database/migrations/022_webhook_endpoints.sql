-- ============================================================================
-- Intakely CRM - Migration 022: Webhook Endpoints
-- ============================================================================
-- Outbound webhook subscriptions for event-driven integrations.
-- Tenant selects which events to subscribe to via JSONB array.
-- ============================================================================

CREATE TABLE IF NOT EXISTS webhook_endpoints (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    url VARCHAR(500) NOT NULL,
    events JSONB NOT NULL DEFAULT '[]'::jsonb,
    secret VARCHAR(255),
    is_active BOOLEAN NOT NULL DEFAULT true,
    last_triggered_at TIMESTAMPTZ,
    failure_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_webhook_endpoints_tenant ON webhook_endpoints (tenant_id) WHERE is_active = true;

-- Row Level Security
ALTER TABLE webhook_endpoints ENABLE ROW LEVEL SECURITY;
ALTER TABLE webhook_endpoints FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON webhook_endpoints
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON webhook_endpoints TO intakely_app;
GRANT SELECT ON webhook_endpoints TO intakely_readonly;
