-- ============================================================================
-- Intakely CRM - Migration 002: Tenants
-- ============================================================================
-- Platform-level table. NO RLS - the application layer controls tenant access.
-- Each tenant represents one customer organization using the CRM.
-- ============================================================================

CREATE TABLE IF NOT EXISTS tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    domain VARCHAR(255) UNIQUE,
    industry VARCHAR(50) NOT NULL DEFAULT 'blank',
    settings JSONB NOT NULL DEFAULT '{}'::jsonb,
    plan VARCHAR(50) NOT NULL DEFAULT 'trial',
    plan_limits JSONB NOT NULL DEFAULT '{}'::jsonb,
    trial_ends_at TIMESTAMPTZ,
    billing_customer_id VARCHAR(255),
    billing_subscription_id VARCHAR(255),
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- settings JSONB expected keys:
-- business_name, logo_url, brand_colors, timezone, business_hours,
-- locale, currency, email_from_name, email_from_address,
-- privacy_url, terms_url

CREATE INDEX idx_tenants_slug ON tenants (slug);
CREATE INDEX idx_tenants_domain ON tenants (domain) WHERE domain IS NOT NULL;
CREATE INDEX idx_tenants_is_active ON tenants (is_active);

-- Grants
GRANT SELECT, INSERT, UPDATE, DELETE ON tenants TO intakely_app;
GRANT SELECT ON tenants TO intakely_readonly;
