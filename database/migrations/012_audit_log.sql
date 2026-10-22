-- ============================================================================
-- Intakely CRM - Migration 012: Audit Log (Partitioned, Append-Only)
-- ============================================================================
-- Immutable audit trail partitioned by month. UPDATE and DELETE are blocked
-- by a trigger. NO RLS - super-admins need cross-tenant access for auditing.
-- ============================================================================

CREATE TABLE IF NOT EXISTS audit_log (
    id UUID NOT NULL DEFAULT gen_random_uuid(),
    tenant_id UUID,
    user_id UUID,
    action VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50),
    resource_id UUID,
    old_data JSONB,
    new_data JSONB,
    ip_address INET,
    user_agent TEXT,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (created_at);

-- -------------------------------------------------------
-- Create partitions for the current month + next 3 months
-- -------------------------------------------------------
DO $$
DECLARE
    start_date DATE;
    end_date DATE;
    partition_name TEXT;
BEGIN
    FOR i IN 0..3 LOOP
        start_date := date_trunc('month', CURRENT_DATE) + (i || ' months')::interval;
        end_date := start_date + '1 month'::interval;
        partition_name := 'audit_log_' || to_char(start_date, 'YYYY_MM');

        EXECUTE format(
            'CREATE TABLE IF NOT EXISTS %I PARTITION OF audit_log
             FOR VALUES FROM (%L) TO (%L)',
            partition_name, start_date, end_date
        );
    END LOOP;
END $$;

-- -------------------------------------------------------
-- Block UPDATE and DELETE to ensure immutability
-- -------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_audit_log_immutable()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'audit_log is append-only: % operations are not permitted', TG_OP;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_audit_log_no_update
    BEFORE UPDATE ON audit_log
    FOR EACH ROW
    EXECUTE FUNCTION fn_audit_log_immutable();

CREATE TRIGGER trg_audit_log_no_delete
    BEFORE DELETE ON audit_log
    FOR EACH ROW
    EXECUTE FUNCTION fn_audit_log_immutable();

-- Indexes
CREATE INDEX idx_audit_log_tenant_created ON audit_log (tenant_id, created_at DESC);
CREATE INDEX idx_audit_log_resource ON audit_log (resource_type, resource_id);
CREATE INDEX idx_audit_log_user ON audit_log (user_id, created_at DESC);

-- NO RLS on audit_log

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON audit_log TO intakely_app;
GRANT SELECT ON audit_log TO intakely_readonly;
