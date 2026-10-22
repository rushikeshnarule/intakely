<?php $this->layout('layouts/app'); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 class="text-title-2">All Enquiries & Leads</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.25rem;">
            Unified table view of all client matters across your organization.
        </p>
    </div>

    <div style="display:flex;gap:0.75rem;align-items:center;">
        <a href="/leads/export" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>Export CSV</span>
        </a>
        <button type="button" onclick="openNewMatterModal()" class="btn btn-primary btn-sm" style="cursor:pointer;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Add Lead</span>
        </button>
    </div>
</div>

<!-- Filter Tabs & Search Bar -->
<div class="card" style="margin-bottom:1.5rem;">
    <div style="padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;gap:0.5rem;">
            <a href="/leads?status=open" class="btn btn-sm <?= ($status === 'open') ? 'btn-primary' : 'btn-ghost' ?>">Open Leads</a>
            <a href="/leads?status=won" class="btn btn-sm <?= ($status === 'won') ? 'btn-primary' : 'btn-ghost' ?>">Instructed (Won)</a>
            <a href="/leads?status=lost" class="btn btn-sm <?= ($status === 'lost') ? 'btn-primary' : 'btn-ghost' ?>">Lost</a>
            <a href="/leads?status=all" class="btn btn-sm <?= ($status === 'all') ? 'btn-primary' : 'btn-ghost' ?>">All (<?= $totalCount ?>)</a>
        </div>

        <form method="GET" action="/leads" style="display:flex;gap:0.5rem;margin:0;">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <input type="text" name="q" class="form-input" style="width:240px;padding:0.35rem 0.75rem;" placeholder="Filter by name, email..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if (!empty($search)): ?>
                <a href="/leads?status=<?= e($status) ?>" class="btn btn-ghost btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Leads Table -->
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Lead Name</th>
                    <th>Service / Matter</th>
                    <th>Stage</th>
                    <th>Score / Priority</th>
                    <th>Est. Value</th>
                    <th>Assignee</th>
                    <th>SLA Timer</th>
                    <th>Received</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:3rem 1rem;color:var(--text-tertiary);">
                            No leads found matching the selected filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leads as $l): ?>
                        <tr>
                            <td>
                                <div>
                                    <a href="/leads/<?= e($l['id']) ?>" style="font-weight:600;color:var(--text-primary);text-decoration:none;">
                                        <?= e($l['name']) ?>
                                    </a>
                                    <div style="font-size:0.75rem;color:var(--text-tertiary);">
                                        <?= e($l['email'] ?: $l['phone'] ?: 'No direct contact') ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-neutral" style="font-size:0.75rem;">
                                    <?= e($l['service'] ?: 'General') ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:0.35rem;">
                                    <span style="width:8px;height:8px;border-radius:50%;background:<?= e($l['stage_color'] ?: '#3b82f6') ?>;"></span>
                                    <span style="font-size:0.8125rem;font-weight:500;"><?= e($l['stage_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:0.35rem;">
                                    <span class="lead-card-priority <?= e($l['priority']) ?>"></span>
                                    <span style="font-size:0.8125rem;font-weight:600;"><?= $l['score'] ?>/100</span>
                                </div>
                            </td>
                            <td>
                                <strong style="font-size:0.875rem;">
                                    <?= !empty($l['value']) ? '£' . number_format((float)$l['value']) : '—' ?>
                                </strong>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;color:var(--text-secondary);">
                                    <?= e($l['assignee_name'] ?: 'Unassigned') ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($l['sla_breached'])): ?>
                                    <span class="sla-timer breached">SLA Breached</span>
                                <?php elseif (!empty($l['sla_deadline']) && $l['status'] === 'open'): ?>
                                    <span class="sla-timer ok" data-sla-deadline="<?= e($l['sla_deadline']) ?>">Active</span>
                                <?php else: ?>
                                    <span style="color:var(--text-tertiary);font-size:0.75rem;">Completed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size:0.75rem;color:var(--text-tertiary);">
                                    <?= date('d M Y, H:i', strtotime($l['created_at'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/leads/<?= e($l['id']) ?>" class="btn btn-ghost btn-sm" style="padding:0.25rem 0.5rem;">
                                    View &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="padding:1rem 1.5rem;border-top:1px solid var(--border-default);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:0.8125rem;color:var(--text-tertiary);">
                Showing page <?= $page ?> of <?= $totalPages ?> (<?= $totalCount ?> total leads)
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="/leads?status=<?= e($status) ?>&page=<?= $page - 1 ?>" class="pagination-btn">&laquo; Previous</a>
                <?php endif; ?>
                <span class="pagination-btn active"><?= $page ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="/leads?status=<?= e($status) ?>&page=<?= $page + 1 ?>" class="pagination-btn">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
