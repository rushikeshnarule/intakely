<?php $this->layout('layouts/app'); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 class="text-title-2">Tasks & Action Items</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.25rem;">
            Follow-up calls, conflict checks, and fee proposal reminders.
        </p>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>Task Description</th>
                    <th>Associated Matter / Lead</th>
                    <th>Assignee</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:3rem 1rem;color:var(--text-tertiary);">
                            No tasks currently scheduled. Automations and matter timelines automatically populate action items here.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $t): ?>
                        <?php
                        $isCompleted = !empty($t['completed_at']);
                        $isOverdue = !$isCompleted && !empty($t['due_at']) && (strtotime($t['due_at']) < time());
                        ?>
                        <tr style="<?= $isCompleted ? 'opacity:0.6;' : '' ?>">
                            <td>
                                <form action="/tasks/<?= e($t['id']) ?>/complete" method="POST" style="margin:0;">
                                    <?= csrf_field() ?>
                                    <input type="checkbox" onchange="this.form.submit()" <?= $isCompleted ? 'checked' : '' ?> style="width:18px;height:18px;cursor:pointer;">
                                </form>
                            </td>
                            <td>
                                <span style="font-weight:600;font-size:0.875rem;<?= $isCompleted ? 'text-decoration:line-through;' : '' ?>">
                                    <?= e($t['title']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($t['lead_id'])): ?>
                                    <a href="/leads/<?= e($t['lead_id']) ?>" style="font-size:0.8125rem;text-decoration:none;font-weight:500;">
                                        <?= e($t['lead_name'] ?: 'View Matter') ?> &rarr;
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-tertiary);font-size:0.8125rem;">General Practice</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;color:var(--text-secondary);">
                                    <?= e($t['assignee_name'] ?: 'Unassigned') ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;<?= $isOverdue ? 'color:var(--text-danger);font-weight:700;' : '' ?>">
                                    <?= !empty($t['due_at']) ? date('d M Y, H:i', strtotime($t['due_at'])) : 'No due date' ?>
                                    <?= $isOverdue ? ' (Overdue)' : '' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $isCompleted ? 'success' : ($isOverdue ? 'danger' : 'neutral') ?>">
                                    <?= $isCompleted ? 'Done' : ($isOverdue ? 'Overdue' : 'Pending') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
