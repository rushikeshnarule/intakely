<?php $this->layout('layouts/app'); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 class="text-title-2">Analytics & Revenue Intelligence</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.25rem;">
            Conversion funnels, campaign channel ROI, and advisor performance leaderboard.
        </p>
    </div>
    <div>
        <a href="/leads/export" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>Export Raw Data</span>
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
    <!-- Inbound Channel Breakdown Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-title-3">Leads by Acquisition Source</h2>
        </div>
        <div class="card-body">
            <div style="max-height:280px;display:flex;justify-content:center;">
                <canvas id="sourceChart"></canvas>
            </div>
            <div style="margin-top:1.5rem;display:flex;flex-direction:column;gap:0.5rem;">
                <?php foreach ($sources as $s): ?>
                    <div style="display:flex;justify-content:space-between;font-size:0.8125rem;">
                        <span style="font-weight:500;text-transform:capitalize;"><?= e(str_replace('_', ' ', $s['source'])) ?></span>
                        <div>
                            <span><?= $s['count'] ?> leads</span> •
                            <strong>£<?= number_format((float)$s['total_val']) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Funnel Drop-off Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-title-3">Pipeline Funnel Velocity</h2>
        </div>
        <div class="card-body">
            <div style="max-height:280px;">
                <canvas id="funnelChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Team Leaderboard -->
<div class="card">
    <div class="card-header">
        <h2 class="text-title-3">Advisor & Team Leaderboard</h2>
        <span class="badge badge-success">Closed Matters & Wins</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Advisor</th>
                    <th>Department / Role</th>
                    <th>Enquiries Handled</th>
                    <th>Matters Won</th>
                    <th>Win Rate (%)</th>
                    <th>Instructed Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($team as $m): ?>
                    <?php
                    $rate = ($m['assigned_count'] > 0) ? round(($m['won_count'] / $m['assigned_count']) * 100) : 0;
                    ?>
                    <tr>
                        <td>
                            <strong style="color:var(--text-primary);"><?= e($m['name']) ?></strong>
                        </td>
                        <td>
                            <span style="font-size:0.8125rem;color:var(--text-secondary);"><?= e($m['department'] ?: $m['role']) ?></span>
                        </td>
                        <td><?= $m['assigned_count'] ?></td>
                        <td>
                            <span class="badge badge-success"><?= $m['won_count'] ?></span>
                        </td>
                        <td>
                            <strong><?= $rate ?>%</strong>
                        </td>
                        <td>
                            <strong style="color:var(--text-success);">£<?= number_format((float)$m['won_val']) ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Source Doughnut Chart
    const sourceCtx = document.getElementById('sourceChart');
    if (sourceCtx) {
        new Chart(sourceCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(fn($s) => ucfirst(str_replace('_', ' ', $s['source'])), $sources)) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($sources, 'count')) ?>,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#64748b'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 2. Funnel Bar Chart
    const funnelCtx = document.getElementById('funnelChart');
    if (funnelCtx) {
        new Chart(funnelCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($funnel, 'name')) ?>,
                datasets: [{
                    label: 'Leads in Stage',
                    data: <?= json_encode(array_column($funnel, 'count')) ?>,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } } }
            }
        });
    }
});
</script>
<?php $this->endSection(); ?>
