-- ============================================================================
-- Intakely CRM - Migration 020: Sessions
-- ============================================================================
-- Database-backed session store. No RLS - system table.
-- Session GC runs via periodic cleanup query.
-- ============================================================================

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id UUID,
    tenant_id UUID,
    ip_address INET,
    user_agent TEXT,
    payload TEXT,
    last_activity INT NOT NULL,

    CONSTRAINT chk_sessions_last_activity CHECK (last_activity > 0)
);

CREATE INDEX idx_sessions_user ON sessions (user_id);
CREATE INDEX idx_sessions_last_activity ON sessions (last_activity);

-- No RLS on sessions

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON sessions TO intakely_app;
GRANT SELECT ON sessions TO intakely_readonly;
