-- ============================================================================
-- Intakely CRM - Migration 011: Appointments & User Availability
-- ============================================================================
-- Calendar-based appointment booking with user availability slots.
-- Integrates with external calendars via calendar_event_id.
-- ============================================================================

CREATE TABLE IF NOT EXISTS appointments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    lead_id UUID REFERENCES leads(id),
    user_id UUID REFERENCES users(id),
    title VARCHAR(255) NOT NULL,
    start_at TIMESTAMPTZ NOT NULL,
    end_at TIMESTAMPTZ NOT NULL,
    location VARCHAR(500),
    notes TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
    reminder_sent BOOLEAN NOT NULL DEFAULT false,
    calendar_event_id VARCHAR(255),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_appointments_status CHECK (status IN (
        'scheduled', 'confirmed', 'completed', 'no_show', 'cancelled'
    )),
    CONSTRAINT chk_appointments_time CHECK (end_at > start_at)
);

CREATE INDEX idx_appointments_user_time ON appointments (user_id, start_at, end_at);
CREATE INDEX idx_appointments_lead ON appointments (lead_id);

-- Row Level Security
ALTER TABLE appointments ENABLE ROW LEVEL SECURITY;
ALTER TABLE appointments FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON appointments
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS user_availability (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    user_id UUID NOT NULL REFERENCES users(id),
    day_of_week INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    buffer_minutes INT NOT NULL DEFAULT 15,
    is_available BOOLEAN NOT NULL DEFAULT true,

    CONSTRAINT chk_user_availability_dow CHECK (day_of_week >= 0 AND day_of_week <= 6),
    CONSTRAINT chk_user_availability_time CHECK (end_time > start_time)
);

CREATE INDEX idx_user_availability_user ON user_availability (user_id, day_of_week);

-- Row Level Security
ALTER TABLE user_availability ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_availability FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON user_availability
    USING (tenant_id = current_setting('app.tenant_id')::uuid)
    WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON appointments TO intakely_app;
GRANT SELECT ON appointments TO intakely_readonly;
GRANT SELECT, INSERT, UPDATE, DELETE ON user_availability TO intakely_app;
GRANT SELECT ON user_availability TO intakely_readonly;
