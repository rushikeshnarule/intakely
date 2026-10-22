-- ============================================================================
-- Intakely CRM - Migration 021: Routing Rules & Scoring Rules
-- ============================================================================
-- Lead routing: assign, round-robin, least-busy, skill-based.
-- Lead scoring: configurable point-based rules.
-- ============================================================================

CREATE TABLE IF NOT EXISTS routing_rules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(255) NOT NULL,
    priority INT NOT NULL DEFAULT 0,
    conditions JSONB NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_config JSONB NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_routing_rules_action_type CHECK (action_type IN (
        'assign_user', 'assign_team', 'round_robin', 'least_busy', 'skill_based'
    ))
);

CREATE INDEX idx_routing_rules_tenant_priority ON routing_rules (tenant_id, priority)
    WHERE is_active = true;

-- Row Level Security
ALTER TABLE routing_rules ENABLE ROW LEVEL SECURITY;
ALTER TABLE routing_rules FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON routing_rules
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS scoring_rules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(255) NOT NULL,
    field VARCHAR(100) NOT NULL,
    operator VARCHAR(20) NOT NULL,
    value TEXT NOT NULL,
    points INT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_scoring_rules_tenant ON scoring_rules (tenant_id) WHERE is_active = true;

-- Row Level Security
ALTER TABLE scoring_rules ENABLE ROW LEVEL SECURITY;
ALTER TABLE scoring_rules FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON scoring_rules
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON routing_rules TO intakely_app;
GRANT SELECT ON routing_rules TO intakely_readonly;
GRANT SELECT, INSERT, UPDATE, DELETE ON scoring_rules TO intakely_app;
GRANT SELECT ON scoring_rules TO intakely_readonly;
