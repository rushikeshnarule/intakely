<?php $this->layout('layouts/marketing'); ?>

<section class="hero" style="padding-bottom: 2rem;">
    <h1>Engineered for high-volume, compliant client intake</h1>
    <p>Everything you need from form capture to instruction, automated communications, and performance analytics.</p>
</section>

<section style="max-width:1000px;margin:2rem auto;padding:0 2rem;">
    <div style="display:flex;flex-direction:column;gap:3rem;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:center;">
            <div>
                <span class="badge badge-primary" style="margin-bottom:0.75rem;">1. Capture & Routing</span>
                <h2 class="text-title-2">Capture leads across every digital channel</h2>
                <p style="color:var(--text-secondary);margin:1rem 0;line-height:1.6;">
                    Drag-and-drop form builder, floating website callback widget, email-to-lead webhook parser, WhatsApp Cloud API, and Zapier-friendly webhooks. Automatically extracts UTM campaign attribution and detects duplicate inquiries using PostgreSQL trigram fuzzy matching.
                </p>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <span class="tag">Honeypot Spam Protection</span>
                    <span class="tag">Round-Robin Routing</span>
                    <span class="tag">After-Hours Tagging</span>
                </div>
            </div>
            <div style="background:var(--bg-secondary);padding:2rem;border-radius:var(--radius-3xl);border:1px solid var(--border-default);">
                <div style="font-weight:600;margin-bottom:0.75rem;">Intelligent Lead Routing Logic</div>
                <div style="font-size:0.8125rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:0.5rem;">
                    <div style="background:white;padding:0.75rem;border-radius:var(--radius-lg);border-left:3px solid var(--accent-500);">
                        <strong>Rule 1:</strong> Urgent hearing keywords -> Escalate to Partner & High Priority
                    </div>
                    <div style="background:white;padding:0.75rem;border-radius:var(--radius-lg);border-left:3px solid var(--text-success);">
                        <strong>Rule 2:</strong> Residential Conveyancing -> Conveyancing Team (Least Busy)
                    </div>
                    <div style="background:white;padding:0.75rem;border-radius:var(--radius-lg);border-left:3px solid var(--text-warning);">
                        <strong>Rule 3:</strong> Weekend enquiry -> Auto-tag "After Hours" & send instant SMS
                    </div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:center;">
            <div style="background:var(--bg-secondary);padding:2rem;border-radius:var(--radius-3xl);border:1px solid var(--border-default);">
                <div style="font-weight:600;margin-bottom:0.75rem;">Multi-Channel Automation Drip</div>
                <div style="display:flex;flex-direction:column;gap:0.5rem;font-size:0.8125rem;">
                    <div style="display:flex;gap:0.75rem;align-items:center;">
                        <span style="background:var(--accent-100);color:var(--accent-800);padding:0.25rem 0.5rem;border-radius:var(--radius-md);font-weight:700;">T+0m</span>
                        <span>Instant personalised email & consultation link</span>
                    </div>
                    <div style="display:flex;gap:0.75rem;align-items:center;">
                        <span style="background:var(--accent-100);color:var(--accent-800);padding:0.25rem 0.5rem;border-radius:var(--radius-md);font-weight:700;">T+2h</span>
                        <span>If unopened -> SMS gentle reminder</span>
                    </div>
                    <div style="display:flex;gap:0.75rem;align-items:center;">
                        <span style="background:var(--accent-100);color:var(--accent-800);padding:0.25rem 0.5rem;border-radius:var(--radius-md);font-weight:700;">T+24h</span>
                        <span>Follow-up call task assigned to intake agent</span>
                    </div>
                </div>
            </div>
            <div>
                <span class="badge badge-success" style="margin-bottom:0.75rem;">2. Follow-Up Automation</span>
                <h2 class="text-title-2">Convert twice as many leads with zero manual effort</h2>
                <p style="color:var(--text-secondary);margin:1rem 0;line-height:1.6;">
                    Visual automation engine with prebuilt recipes for quotes, consultation reminders, and 30-day cold lead reactivation. Strictly adheres to per-contact opt-outs (STOP/Unsubscribe) and configurable quiet hours.
                </p>
                <a href="/register" class="btn btn-primary">Try Automation Builder</a>
            </div>
        </div>
    </div>
</section>
