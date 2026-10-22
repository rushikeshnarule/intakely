# Intakely — Commercial Multi-Tenant SaaS CRM

**Intakely** is a multi-tenant SaaS CRM engineered by **Knot8 Technologies** for professional service firms (Law Firms, Medical & Dental Clinics, Real Estate Agencies, Creative Agencies, Home Services, and Chartered Accountants).

> **Product Promise:** *"Never lose a lead. Every enquiry captured, routed, answered and followed up automatically, in minutes, with a clean audit trail."*

---

## ⚡ Quick Setup (Runs locally in 3 minutes)

Intakely requires **PHP 8.2+** and **PostgreSQL 16**. It has no Node.js build step or heavy framework dependencies.

### 1. Start PostgreSQL 16
```bash
docker compose up -d
```

### 2. Copy Environment & Run Migrations + Seeders
```bash
cp .env.example .env
php bin/migrate.php
php bin/seed.php
```

### 3. Start Local Web Server
```bash
php -S localhost:8000 -t public
```
Visit **[http://localhost:8000](http://localhost:8000)** in your browser.

---

## 🎭 2-Minute Guided Demo Script

Follow this step-by-step walkthrough to evaluate the headline features:

### Step 1: Sign in with One-Click Demo Personas
1. Navigate to `http://localhost:8000/login`.
2. Notice the **One-Click Demo Logins** panel.
3. Click **Eleanor Vance (Apex Law LLP, Owner)** to automatically sign into the Law Firm tenant.
4. You will arrive at the **Pipeline Board** (`/pipeline`) showing 45 realistic legal intake leads categorized into Personal Legal Services stages.

### Step 2: Live Intake Capture via Public Form
1. Open a new incognito window (or second tab) and visit the public branded intake form:
   `http://localhost:8000/f/legal-intake`
2. Submit a new enquiry:
   - **Full Name**: `Lady Genevieve Sterling`
   - **Email**: `genevieve.sterling@example.com`
   - **Phone**: `+44 7700 900888`
   - **Area of Law**: `Commercial Litigation`
   - **Details**: `Urgent injunction hearing scheduled next Tuesday at the High Court.`
   - Check the consent box and click **Submit Enquiry**.

### Step 3: Verify Live Pipeline Ingestion & SLA Countdown
1. Return to your Eleanor Vance dashboard (`/pipeline`).
2. Notice that **Lady Genevieve Sterling** has immediately appeared under **New Enquiry** without refreshing the page (via Server-Sent Events).
3. Notice that the urgency keyword (`injunction`) triggered automated scoring:
   - **Score**: 80+/100
   - **Priority**: High (Red dot)
   - **SLA Timer**: Active 2-hour countdown warning badge.
   - **Tag**: `Urgent Hearing` automatically appended.

### Step 4: Compliance Stage Gates & Audit Logging
1. Click on **Lady Genevieve Sterling** to inspect the Lead Details page (`/leads/{id}`).
2. Review the **Unified Activity Timeline**: the automated instant acknowledgement email has already been logged.
3. Test the **AI Assistant** in the right sidebar:
   - Click **Summarize Matter** to synthesize the legal brief.
   - Click **Suggest Next Action** to view recommended steps.
   - Click **Draft Warm Reply** to generate an empathetic email auto-filled into the reply composer.
4. Attempt to change the stage to **Instructed**:
   - The compliance validator blocks the transition with an alert: `Cannot move to Instructed without: Conflict Check Passed, ID/AML Verified`.

### Step 5: Verify Multi-Tenant Database Isolation
1. Click **Sign Out** in the sidebar.
2. Sign in as **Dr. Alistair Finch (Smile Dental Clinic)** (`alistair@example.com` / `password123`).
3. You are now in the Dental Clinic tenant (`smile-dental`).
4. Notice that **all legal leads have completely disappeared**. The board now displays patient treatment lanes (Invisalign, Implants, Whitening, No-Show Recovery).
5. Even if you attempt to paste Genevieve Sterling's lead UUID directly into the URL, PostgreSQL Row-Level Security returns 0 rows.

---

## 🧪 Automated Test Suite

Verify all architectural guarantees (including database-level RLS isolation):

```bash
php bin/test.php
```

### Verified Test Suites:
- `TenantIsolationTest`: Proves cross-tenant reads, updates, deletes, and spoofed inserts are blocked by PostgreSQL RLS.
- `RoutingRulesTest`: Validates urgency keyword escalation, after-hours detection, and round-robin assignment.
- `SlaCalculationTest`: Verifies 2-hour (High) vs 24-hour (Normal) SLA deadlines.
- `AutomationEngineTest`: Verifies trigger/action execution and opt-out suppression (`do_not_contact`).
- `BillingLimitsTest`: Verifies plan quotas (users, leads/mo, automations) and upgrades.

---

## ⚙️ Background Workers & Periodic Scheduler

### Background Job Worker
Intakely processes async jobs (emails, webhooks, heavy reports) using PostgreSQL `SELECT ... FOR UPDATE SKIP LOCKED`:

```bash
# Continuous worker loop
php bin/worker.php

# Single-pass execution (for testing or CI)
php bin/worker.php --once
```

### Periodic Cron Scheduler
Scans for SLA breaches, updates countdowns, and refreshes analytics materialized views:

```bash
# Add to server crontab:
* * * * * php /path/to/intakely/bin/scheduler.php >> /path/to/intakely/storage/logs/cron.log 2>&1
```

*Note: For live demos, you can trigger queue execution on demand by visiting **Super-Admin HQ > Background Jobs** (`/admin/jobs`) and clicking "⚡ Run Pending Jobs Now".*

---

## 🚀 Production Deployment Architecture

```
Internet -> [Cloudflare / SSL Termination]
              |
         [Nginx Reverse Proxy]
              |
         [PHP 8.2-FPM Workers] (Vanilla MVC, non-framework, ultra low memory)
              |
         [pgBouncer (Session Pooling)]
              |
         [PostgreSQL 16 Database Cluster]
```

### 1. Connection Pooling with pgBouncer
Because Intakely uses `SET LOCAL app.tenant_id = '...'` inside transactions, **Session Pooling** or **Transaction Pooling** with transaction-level parameters is fully supported. Always ensure `SET LOCAL` is called within `BEGIN ... COMMIT` blocks (as implemented in `TenantResolutionMiddleware`).

### 2. Environment Variables (.env)
- `APP_ENV=production`
- `DB_USER=intakely_app` (strictly unprivileged, non-owner role)
- `DB_MIGRATOR_USER=intakely_owner` (used only during deployments)
- `ENCRYPTION_KEY`: 32-byte base64 string for libsodium custom field encryption.

### 3. Automated Backups
```bash
# Nightly encrypted PostgreSQL backup
pg_dump -U intakely_owner -Fc intakely | gpg -c > /backups/intakely_$(date +%Y%m%d).dump.gpg
```

---

## 🛡️ Security Baseline Checklist

- [x] **PostgreSQL Row-Level Security (RLS)**: Enforced with `FORCE ROW LEVEL SECURITY` on all tenant tables.
- [x] **Least Privilege Roles**: App connects as non-superuser `intakely_app`; schema migrations run as `intakely_owner`.
- [x] **Zero Raw SQL Concatenation**: Prepared statements with parameterized PDO bindings exclusively.
- [x] **Password Hashing**: Modern Argon2id (`PASSWORD_ARGON2ID`) with brute-force lockout throttling (5 attempts / 15 minutes).
- [x] **CSRF Protection**: Synchronizer token pattern verified on all state-changing POST requests.
- [x] **Security Headers**: HSTS, CSP, X-Content-Type-Options, Referrer-Policy, and X-Frame-Options.
- [x] **Immutable Audit Log**: PostgreSQL trigger prevents `UPDATE` and `DELETE` operations on `audit_log`.
- [x] **Field Encryption at Rest**: Sensitive client data encrypted via libsodium `XChaCha20-Poly1305`.

---

## 📚 Technical Documentation Directory

- [`docs/MULTI-TENANCY.md`](docs/MULTI-TENANCY.md): Comprehensive deep-dive on PostgreSQL RLS architecture and threat model.
- [`docs/AUTOMATIONS.md`](docs/AUTOMATIONS.md): Automation engine specifications, conditions syntax, and prebuilt recipes.
- [`docs/WORDPRESS-EMBED.md`](docs/WORDPRESS-EMBED.md): WordPress iframe embeds, floating callback widgets, and webhooks.
- [`docs/API.md`](docs/API.md): Public REST API v1 endpoints and headers.
- [`docs/openapi.yaml`](docs/openapi.yaml): OpenAPI 3.0 specification file.
