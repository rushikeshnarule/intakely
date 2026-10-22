<?php $this->layout('layouts/admin'); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 class="text-title-2">Background Job Queue</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;">
            PostgreSQL-backed job queue via <code>SELECT ... FOR UPDATE SKIP LOCKED</code>.
        </p>
    </div>

    <!-- The required "Run now" button for demo purposes -->
    <form action="/admin/jobs/run" method="POST" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary">
            ⚡ Run Pending Jobs Now (Demo)
        </button>
    </form>
</div>

<!-- Stats -->
<div class="stat-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-card-label">Pending In Queue</div>
        <div class="stat-card-value"><?= $stats['pending'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Currently Executing</div>
        <div class="stat-card-value"><?= $stats['running'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Completed (Last 24h)</div>
        <div class="stat-card-value" style="color:var(--text-success);"><?= $stats['completed_24h'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-label">Failed (Last 24h)</div>
        <div class="stat-card-value" style="<?= ($stats['failed_24h'] > 0) ? 'color:var(--text-danger);' : '' ?>"><?= $stats['failed_24h'] ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="text-title-3">Recent Background Jobs</h2>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Queue</th>
                    <th>Attempts</th>
                    <th>Available At</th>
                    <th>Completed At</th>
                    <th>Failed At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentJobs)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:2rem;color:var(--text-tertiary);">
                            Queue is currently empty.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentJobs as $j): ?>
                        <tr>
                            <td><code><?= substr($j['id'], 0, 8) ?>...</code></td>
                            <td><span class="badge badge-neutral"><?= e($j['queue']) ?></span></td>
                            <td><?= $j['attempts'] ?> / <?= $j['max_attempts'] ?></td>
                            <td><span style="font-size:0.75rem;"><?= date('H:i:s', strtotime($j['available_at'])) ?></span></td>
                            <td>
                                <?php if (!empty($j['completed_at'])): ?>
                                    <span class="badge badge-success">✓ <?= date('H:i:s', strtotime($j['completed_at'])) ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($j['failed_at'])): ?>
                                    <span class="badge badge-danger">Failed</span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
