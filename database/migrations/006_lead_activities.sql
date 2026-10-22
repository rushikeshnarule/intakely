-- ============================================================================
-- Intakely CRM - Migration 006: Lead Activities
-- ============================================================================
-- Immutable timeline of everything that happens to a lead.
-- Notes, emails, calls, stage changes, automations, etc.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lead_activities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    lead_id UUID NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    user_id UUID,
    type VARCHAR(50) NOT NULL,
    content TEXT,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    is_internal BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_lead_activities_type CHECK (type IN (
        'note', 'email_sent', 'email_received', 'sms_sent', 'sms_received',
        'whatsapp_sent', 'whatsapp_received', 'call', 'stage_change',
        'assignment', 'tag_change', 'task_created', 'task_completed',
        'automation', 'form_submission', 'system'
    ))
);

CREATE INDEX idx_lead_activities_lead_created ON lead_activities (lead_id, created_at DESC);

-- Row Level Security
ALTER TABLE lead_activities ENABLE ROW LEVEL SECURITY;
ALTER TABLE lead_activities FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON lead_activities
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON lead_activities TO intakely_app;
GRANT SELECT ON lead_activities TO intakely_readonly;
