-- ============================================================================
-- Intakely CRM - Migration 018: Files
-- ============================================================================
-- File attachments linked to leads. Metadata only - actual files
-- are stored on disk/S3, referenced by storage_path.
-- ============================================================================

CREATE TABLE IF NOT EXISTS files (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    lead_id UUID REFERENCES leads(id),
    uploaded_by UUID,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_files_lead ON files (lead_id);
CREATE INDEX idx_files_tenant ON files (tenant_id, created_at DESC);

-- Row Level Security
ALTER TABLE files ENABLE ROW LEVEL SECURITY;
ALTER TABLE files FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON files
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON files TO intakely_app;
GRANT SELECT ON files TO intakely_readonly;
