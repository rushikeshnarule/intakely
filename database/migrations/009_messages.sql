-- ============================================================================
-- Intakely CRM - Migration 009: Messages & Email Templates
-- ============================================================================
-- Unified messaging table for email, SMS, and WhatsApp with direction
-- tracking. Template engine supports {{variable}} placeholders.
-- ============================================================================

CREATE TABLE IF NOT EXISTS messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    lead_id UUID NOT NULL REFERENCES leads(id),
    user_id UUID,
    channel VARCHAR(20) NOT NULL,
    direction VARCHAR(10) NOT NULL,
    from_address VARCHAR(255),
    to_address VARCHAR(255),
    subject VARCHAR(500),
    body TEXT,
    template_id UUID,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    status VARCHAR(20) NOT NULL DEFAULT 'queued',
    sent_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_messages_channel CHECK (channel IN ('email', 'sms', 'whatsapp')),
    CONSTRAINT chk_messages_direction CHECK (direction IN ('inbound', 'outbound')),
    CONSTRAINT chk_messages_status CHECK (status IN ('queued', 'sent', 'delivered', 'failed', 'bounced'))
);

CREATE INDEX idx_messages_lead ON messages (lead_id, created_at DESC);
CREATE INDEX idx_messages_tenant_status ON messages (tenant_id, status)
    WHERE status IN ('queued', 'failed');

-- Row Level Security
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;
ALTER TABLE messages FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON messages
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS email_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    channel VARCHAR(20) NOT NULL DEFAULT 'email',
    category VARCHAR(50),
    is_system BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_email_templates_tenant_slug UNIQUE (tenant_id, slug)
);

-- Row Level Security
ALTER TABLE email_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE email_templates FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON email_templates
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON messages TO intakely_app;
GRANT SELECT ON messages TO intakely_readonly;
GRANT SELECT, INSERT, UPDATE, DELETE ON email_templates TO intakely_app;
GRANT SELECT ON email_templates TO intakely_readonly;
