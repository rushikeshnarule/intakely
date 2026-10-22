<?php $this->layout('layouts/admin'); ?>

<div style="margin-bottom:2rem;">
    <h1 class="text-title-2">Platform Infrastructure & Multi-Tenancy Overview</h1>
    <p style="color:var(--text-secondary);font-size:0.875rem;">
        Master telemetry across all customer accounts, PostgreSQL database health, and queue latency.
    </p>
</div>

<!-- High-level KPIs -->
<div class="stat-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-card-label">Active Customer Tenants</div>
        <div class="stat-card-value"><?= $stats['active_tenants'] ?></div>
        <div class="stat-card-trend up"><span><?= $stats['total_tenants'] ?> total registered</span></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-label">Monthly Recurring Revenue (MRR)</div>
        <div class="stat-card-value">£<?= number_format($stats['mrr']) ?></div>
        <div class="stat-card-trend up"><span>From live subscriptions</span></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-label">Platform-wide Leads Captured</div>
        <div class="stat-card-value"><?= number_format($stats['total_leads']) ?></div>
        <div class="stat-card-trend up"><span>Across all pipelines</span></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-label">Job Queue Pending</div>
        <div class="stat-card-value"><?= $queueStats['pending'] ?></div>
        <div class="stat-card-trend <?= ($queueStats['pending'] > 10) ? 'down' : 'up' ?>">
            <span><?= $queueStats['running'] ?> executing</span>
        </div>
    </div>
</div>

<!-- Tenants List Preview -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h2 class="text-title-3">Organizations & Subdomains</h2>
        <a href="/admin/tenants" class="btn btn-secondary btn-sm">View Full Directory &rarr;</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Organization</th>
                    <th>Subdomain / Host</th>
                    <th>Industry Template</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $t): ?>
                    <tr>
                        <td>
                            <strong style="color:var(--text-primary);"><?= e($t['name']) ?></strong>
                        </td>
                        <td>
                            <span style="font-family:var(--font-mono);font-size:0.8125rem;"><?= e($t['slug']) ?>.localhost</span>
                        </td>
                        <td>
                            <span class="badge badge-neutral" style="text-transform:capitalize;">
                                <?= e(str_replace('_', ' ', $t['industry'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?= ucfirst(e($t['plan'])) ?></span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $t['is_active'] ? 'success' : 'neutral' ?>">
                                <?= $t['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <form action="/admin/tenants/<?= e($t['id']) ?>/impersonate" method="POST" style="margin:0;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.75rem;" title="Sign in as tenant owner with audit trail">
                                    Impersonate (Audited)
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
