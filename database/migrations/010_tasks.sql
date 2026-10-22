-- ============================================================================
-- Intakely CRM - Migration 010: Tasks
-- ============================================================================
-- Follow-up tasks linked to leads with due dates and priority.
-- ============================================================================

CREATE TABLE IF NOT EXISTS tasks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    lead_id UUID REFERENCES leads(id),
    assigned_to UUID REFERENCES users(id),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_at TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    priority VARCHAR(10) NOT NULL DEFAULT 'normal',
    created_by UUID,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_tasks_priority CHECK (priority IN ('high', 'normal', 'low'))
);

CREATE INDEX idx_tasks_assigned_due ON tasks (assigned_to, due_at)
    WHERE completed_at IS NULL;
CREATE INDEX idx_tasks_lead ON tasks (lead_id);

-- Row Level Security
ALTER TABLE tasks ENABLE ROW LEVEL SECURITY;
ALTER TABLE tasks FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON tasks
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON tasks TO intakely_app;
GRANT SELECT ON tasks TO intakely_readonly;
