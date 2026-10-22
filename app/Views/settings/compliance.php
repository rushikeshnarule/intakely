<?php $this->layout('layouts/app'); ?>

<div style="margin-bottom:1.5rem;">
    <h1 class="text-title-2">Compliance, Trust & Immutable Audit Log</h1>
    <p style="color:var(--text-secondary);font-size:0.875rem;">
        SRA, HIPAA & GDPR compliance: append-only audit trail partitioned monthly, DSAR tools, and consent records.
    </p>
</div>

<div class="tabs" style="margin-bottom:1.5rem;">
    <a href="/settings" class="tab">General</a>
    <a href="/settings/team" class="tab">Team & Roles</a>
    <a href="/settings/compliance" class="tab active">Compliance & Audit Log</a>
    <a href="/settings/billing" class="tab">Plans & Billing</a>
</div>

<!-- DSAR Actions Bar -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
    <!-- DSAR Export Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-title-3" style="font-size:1rem;">Data Subject Access Request (DSAR)</h2>
        </div>
        <div class="card-body">
            <p style="font-size:0.8125rem;color:var(--text-secondary);margin-bottom:1rem;">
                Generate a comprehensive machine-readable export of all records, activities, messages, and notes for a client.
            </p>
            <form action="/settings/data-export" method="POST" target="_blank" style="display:flex;gap:0.5rem;">
                <?= csrf_field() ?>
                <input type="text" name="identifier" class="form-input" placeholder="Client email or phone..." required>
                <button type="submit" class="btn btn-secondary btn-sm" style="white-space:nowrap;">Export Data</button>
            </form>
        </div>
    </div>

    <!-- GDPR Erasure Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-title-3" style="font-size:1rem;">Right to Erasure / Anonymization</h2>
        </div>
        <div class="card-body">
            <p style="font-size:0.8125rem;color:var(--text-secondary);margin-bottom:1rem;">
                Irreversibly scrubs all identifying details from a lead while preserving structural counts for reporting.
            </p>
            <form action="/settings/data-erasure" method="POST" onsubmit="return confirm('Are you certain? This action is permanent and recorded in the immutable audit log.');" style="display:flex;gap:0.5rem;">
                <?= csrf_field() ?>
                <input type="text" name="lead_id" class="form-input" placeholder="Lead UUID..." required>
                <button type="submit" class="btn btn-danger btn-sm" style="white-space:nowrap;">Anonymize Lead</button>
            </form>
        </div>
    </div>
</div>

<!-- Immutable Audit Log Table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h2 class="text-title-3">Immutable Audit Trail</h2>
            <div style="font-size:0.75rem;color:var(--text-tertiary);">PostgreSQL trigger enforces append-only: updates and deletes are blocked.</div>
        </div>
        <span class="badge badge-success">Cryptographically Verifiable</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Actor / User</th>
                    <th>Resource</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:2rem 1rem;color:var(--text-tertiary);">
                            No audit log entries recorded.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="font-size:0.75rem;color:var(--text-tertiary);">
                                <?= date('d M Y, H:i:s', strtotime($l['created_at'])) ?>
                            </td>
                            <td>
                                <span class="badge badge-primary" style="font-family:var(--font-mono);font-size:0.75rem;">
                                    <?= e($l['action']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;font-weight:500;">
                                    <?= e($l['user_name'] ?: 'System Process') ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size:0.75rem;color:var(--text-secondary);">
                                    <?= e($l['resource_type'] ?: 'system') ?> <?= !empty($l['resource_id']) ? '#' . substr($l['resource_id'], 0, 8) : '' ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size:0.75rem;font-family:var(--font-mono);color:var(--text-tertiary);">
                                    <?= e((string)$l['ip_address']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
