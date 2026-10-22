<?php $this->layout('layouts/app'); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 class="text-title-2">Automation Workflows</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.25rem;">
            Rule-based multi-step triggers, quiet hours compliance, and execution audit history.
        </p>
    </div>
</div>

<div class="card" style="margin-bottom:2rem;">
    <div class="card-header">
        <h2 class="text-title-3">Active Workflow Recipes</h2>
        <span class="badge badge-success"><?= count($automations) ?> Installed</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Workflow Name</th>
                    <th>Trigger Event</th>
                    <th>Conditions</th>
                    <th>Actions Configured</th>
                    <th>Total Runs</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($automations)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:3rem 1rem;color:var(--text-tertiary);">
                            No automations configured.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($automations as $auto): ?>
                        <?php
                        $conds = is_string($auto['conditions']) ? json_decode($auto['conditions'], true) : ($auto['conditions'] ?? []);
                        $acts = is_string($auto['actions']) ? json_decode($auto['actions'], true) : ($auto['actions'] ?? []);
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <strong style="color:var(--text-primary);"><?= e($auto['name']) ?></strong>
                                    <?php if (!empty($auto['last_run_at'])): ?>
                                        <div style="font-size:0.6875rem;color:var(--text-tertiary);">
                                            Last run <?= date('d M, H:i', strtotime($auto['last_run_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary">
                                    ⚡ <?= e(str_replace('_', ' ', $auto['trigger_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;color:var(--text-secondary);">
                                    <?= empty($conds) ? 'Always run' : count($conds) . ' condition(s)' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.25rem;flex-wrap:wrap;">
                                    <?php foreach ($acts as $a): ?>
                                        <span class="tag">
                                            <?= e(str_replace('_', ' ', $a['type'] ?? 'action')) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?= (int) ($auto['run_count'] ?? 0) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?= $auto['is_active'] ? 'success' : 'neutral' ?>">
                                    <?= $auto['is_active'] ? 'Active' : 'Paused' ?>
                                </span>
                            </td>
                            <td>
                                <form action="/automations/<?= e($auto['id']) ?>/toggle" method="POST" style="margin:0;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">
                                        <?= $auto['is_active'] ? 'Pause' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Execution Run Logs -->
<div class="card">
    <div class="card-header">
        <h2 class="text-title-3">Recent Execution Logs</h2>
        <span style="font-size:0.75rem;color:var(--text-tertiary);">Per-run execution trail</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Automation</th>
                    <th>Lead</th>
                    <th>Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:2rem 1rem;color:var(--text-tertiary);">
                            No automation runs logged yet. Submitting a new lead or changing stages will trigger execution.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td style="font-size:0.75rem;color:var(--text-tertiary);">
                                <?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <strong style="font-size:0.8125rem;"><?= e($log['automation_name']) ?></strong>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;"><?= e($log['lead_name'] ?: 'System Lead') ?></span>
                            </td>
                            <td>
                                <span style="font-size:0.75rem;font-family:var(--font-mono);"><?= $log['duration_ms'] ?>ms</span>
                            </td>
                            <td>
                                <span class="badge badge-<?= ($log['status'] === 'success') ? 'success' : 'danger' ?>">
                                    <?= e($log['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
