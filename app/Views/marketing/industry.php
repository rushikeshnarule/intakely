<?php $this->layout('layouts/marketing'); ?>

<section class="hero" style="padding-bottom: 2rem;">
    <div style="display:inline-flex;padding:0.25rem 0.75rem;border-radius:var(--radius-full);background:var(--bg-accent);color:var(--accent-700);font-size:0.8125rem;font-weight:600;margin-bottom:1.5rem;">
        Industry Template
    </div>
    <h1>Intakely for <?= e($industryName) ?></h1>
    <p>Pre-configured pipeline stages, compliance fields, intake forms, and automated drips tailored out of the box for <?= strtolower(e($industryName)) ?>.</p>
    <div class="hero-cta">
        <a href="/register?industry=<?= e($slug) ?>" class="btn btn-primary btn-lg">Deploy this Template (14 Days Free)</a>
        <a href="/login" class="btn btn-secondary btn-lg">View Live Sandbox</a>
    </div>
</section>

<section style="max-width:960px;margin:2rem auto;padding:0 2rem;">
    <div class="card" style="margin-bottom:3rem;">
        <div class="card-header">
            <h2 class="text-title-3">What comes pre-configured in this template</h2>
            <span class="badge badge-success">100% Editable After Setup</span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
                <div>
                    <h3 style="font-size:0.9375rem;font-weight:600;margin-bottom:0.75rem;color:var(--accent-700);">Pipeline Stages</h3>
                    <ul style="list-style:disc;margin-left:1.25rem;font-size:0.875rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:0.35rem;">
                        <li>New Inbound Enquiry (2h SLA timer)</li>
                        <li>Contacted / Qualification</li>
                        <li>Consultation / Discovery Booked</li>
                        <li>Compliance & Verification Check</li>
                        <li>Formal Quote / Fee Proposal Sent</li>
                        <li>Instructed / Retainer Paid (Won)</li>
                        <li>Archive / Lost</li>
                    </ul>
                </div>

                <div>
                    <h3 style="font-size:0.9375rem;font-weight:600;margin-bottom:0.75rem;color:var(--accent-700);">Prebuilt Automations & Forms</h3>
                    <ul style="list-style:disc;margin-left:1.25rem;font-size:0.875rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:0.35rem;">
                        <li>Branded online intake form (/f/<?= e($slug) ?>-intake)</li>
                        <li>Instant SMS & email enquiry acknowledgement</li>
                        <li>Urgent keyword detector (triggers partner escalation)</li>
                        <li>24h & 72h automated quote follow-up drips</li>
                        <li>No-show consultation recovery sequence</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
