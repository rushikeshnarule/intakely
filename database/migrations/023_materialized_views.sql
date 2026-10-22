-- ============================================================================
-- Intakely CRM - Migration 023: Materialized Views (Analytics)
-- ============================================================================
-- Pre-computed analytics views refreshed periodically.
-- Used by dashboards and reporting endpoints.
-- ============================================================================

-- -------------------------------------------------------
-- mv_lead_stats: Daily lead metrics by tenant
-- -------------------------------------------------------
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_lead_stats AS
SELECT
    l.tenant_id,
    date_trunc('day', l.created_at)::date AS date,
    l.source,
    l.pipeline_id,
    COUNT(*) FILTER (WHERE l.created_at >= date_trunc('day', l.created_at)) AS new_leads,
    COUNT(*) FILTER (WHERE l.status = 'won') AS won_leads,
    COUNT(*) FILTER (WHERE l.status = 'lost') AS lost_leads,
    AVG(
        EXTRACT(EPOCH FROM (l.first_response_at - l.created_at))
    ) FILTER (WHERE l.first_response_at IS NOT NULL) AS avg_first_response_seconds,
    AVG(l.value) FILTER (WHERE l.value IS NOT NULL) AS avg_value,
    COUNT(*) FILTER (WHERE l.sla_breached = true) AS sla_breaches
FROM leads l
GROUP BY l.tenant_id, date_trunc('day', l.created_at)::date, l.source, l.pipeline_id
WITH NO DATA;

CREATE UNIQUE INDEX idx_mv_lead_stats_pk
    ON mv_lead_stats (tenant_id, date, source, pipeline_id);

-- -------------------------------------------------------
-- mv_pipeline_funnel: Current pipeline stage distribution
-- -------------------------------------------------------
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_pipeline_funnel AS
SELECT
    l.tenant_id,
    l.pipeline_id,
    l.stage_id,
    ps.name AS stage_name,
    ps.sort_order,
    COUNT(*) AS lead_count,
    COALESCE(SUM(l.value), 0) AS total_value,
    AVG(
        EXTRACT(EPOCH FROM (NOW() - l.updated_at)) / 86400.0
    ) AS avg_days_in_stage
FROM leads l
JOIN pipeline_stages ps ON ps.id = l.stage_id
WHERE l.status = 'open'
GROUP BY l.tenant_id, l.pipeline_id, l.stage_id, ps.name, ps.sort_order
WITH NO DATA;

CREATE UNIQUE INDEX idx_mv_pipeline_funnel_pk
    ON mv_pipeline_funnel (tenant_id, pipeline_id, stage_id);

-- -------------------------------------------------------
-- mv_user_performance: Daily user metrics
-- -------------------------------------------------------
CREATE MATERIALIZED VIEW IF NOT EXISTS mv_user_performance AS
WITH lead_metrics AS (
    SELECT
        tenant_id,
        assigned_to AS user_id,
        date_trunc('day', created_at)::date AS date,
        COUNT(*) AS leads_assigned,
        COUNT(*) FILTER (WHERE status = 'won') AS leads_won,
        AVG(EXTRACT(EPOCH FROM (first_response_at - created_at))) FILTER (WHERE first_response_at IS NOT NULL) AS avg_response_time
    FROM leads
    WHERE assigned_to IS NOT NULL
    GROUP BY tenant_id, assigned_to, date_trunc('day', created_at)::date
),
task_metrics AS (
    SELECT
        tenant_id,
        assigned_to AS user_id,
        date_trunc('day', completed_at)::date AS date,
        COUNT(*) AS tasks_completed
    FROM tasks
    WHERE assigned_to IS NOT NULL AND completed_at IS NOT NULL
    GROUP BY tenant_id, assigned_to, date_trunc('day', completed_at)::date
)
SELECT
    COALESCE(lm.tenant_id, tm.tenant_id) AS tenant_id,
    COALESCE(lm.user_id, tm.user_id) AS user_id,
    COALESCE(lm.date, tm.date) AS date,
    COALESCE(lm.leads_assigned, 0) AS leads_assigned,
    COALESCE(lm.leads_won, 0) AS leads_won,
    lm.avg_response_time,
    COALESCE(tm.tasks_completed, 0) AS tasks_completed
FROM lead_metrics lm
FULL OUTER JOIN task_metrics tm
    ON lm.tenant_id = tm.tenant_id
   AND lm.user_id = tm.user_id
   AND lm.date = tm.date
WITH NO DATA;

CREATE UNIQUE INDEX idx_mv_user_performance_pk
    ON mv_user_performance (tenant_id, user_id, date);

-- Grants (materialized views are read-only)
GRANT SELECT ON mv_lead_stats TO intakely_app;
GRANT SELECT ON mv_lead_stats TO intakely_readonly;
GRANT SELECT ON mv_pipeline_funnel TO intakely_app;
GRANT SELECT ON mv_pipeline_funnel TO intakely_readonly;
GRANT SELECT ON mv_user_performance TO intakely_app;
GRANT SELECT ON mv_user_performance TO intakely_readonly;
