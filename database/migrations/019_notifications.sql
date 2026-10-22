-- ============================================================================
-- Intakely CRM - Migration 019: Notifications
-- ============================================================================
-- In-app notification feed for users. Optimized for unread queries.
-- ============================================================================

CREATE TABLE IF NOT EXISTS notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    user_id UUID NOT NULL REFERENCES users(id),
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    data JSONB NOT NULL DEFAULT '{}'::jsonb,
    read_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Optimized for "unread notifications" query
CREATE INDEX idx_notifications_user_unread ON notifications (user_id, read_at)
    WHERE read_at IS NULL;

CREATE INDEX idx_notifications_user_created ON notifications (user_id, created_at DESC);

-- Row Level Security
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON notifications
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON notifications TO intakely_app;
GRANT SELECT ON notifications TO intakely_readonly;
