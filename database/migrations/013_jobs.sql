-- ============================================================================
-- Intakely CRM - Migration 013: Background Job Queue
-- ============================================================================
-- Simple database-backed job queue. No RLS - system table used by workers.
-- Jobs are picked up with SELECT ... FOR UPDATE SKIP LOCKED.
-- ============================================================================

CREATE TABLE IF NOT EXISTS jobs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    queue VARCHAR(50) NOT NULL DEFAULT 'default',
    payload JSONB NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 3,
    available_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    reserved_at TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    failed_at TIMESTAMPTZ,
    error TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Workers query: WHERE completed_at IS NULL AND failed_at IS NULL
CREATE INDEX idx_jobs_queue_available ON jobs (queue, available_at)
    WHERE completed_at IS NULL AND failed_at IS NULL;

-- No RLS on jobs

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON jobs TO intakely_app;
GRANT SELECT ON jobs TO intakely_readonly;
