<?php $this->layout('layouts/app'); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 class="text-title-2">Appointments & Consultations</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.25rem;">
            Scheduled consultations, viewing appointments, and public calendar booking links.
        </p>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Consultation Title</th>
                    <th>Client / Lead</th>
                    <th>Advisor / Host</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:3rem 1rem;color:var(--text-tertiary);">
                            No appointments currently scheduled.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong style="color:var(--text-primary);"><?= date('D, d M Y', strtotime($apt['start_at'])) ?></strong>
                                    <div style="font-size:0.75rem;color:var(--text-tertiary);">
                                        <?= date('H:i', strtotime($apt['start_at'])) ?> – <?= date('H:i', strtotime($apt['end_at'])) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight:600;"><?= e($apt['title']) ?></span>
                            </td>
                            <td>
                                <div>
                                    <a href="/leads/<?= e($apt['lead_id']) ?>" style="font-weight:600;color:var(--text-primary);text-decoration:none;">
                                        <?= e($apt['lead_name'] ?: 'Enquiry Client') ?>
                                    </a>
                                    <div style="font-size:0.75rem;color:var(--text-tertiary);"><?= e($apt['lead_email'] ?: $apt['lead_phone']) ?></div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;color:var(--text-secondary);"><?= e($apt['user_name'] ?: 'Team Member') ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= ($apt['status'] === 'scheduled') ? 'primary' : (($apt['status'] === 'completed') ? 'success' : 'danger') ?>" style="text-transform:capitalize;">
                                    <?= e($apt['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/appointments/<?= e($apt['id']) ?>/ics" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">
                                    Download .ICS
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
