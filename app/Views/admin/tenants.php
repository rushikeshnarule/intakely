<?php $this->layout('layouts/admin'); ?>

<div style="margin-bottom:2rem;">
    <h1 class="text-title-2">All Customer Tenants</h1>
    <p style="color:var(--text-secondary);font-size:0.875rem;">Manage subscription plan overrides and audit-logged impersonation.</p>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Organization Name</th>
                    <th>Subdomain</th>
                    <th>Industry</th>
                    <th>Current Plan</th>
                    <th>Override Plan</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $t): ?>
                    <tr>
                        <td>
                            <div>
                                <strong style="color:var(--text-primary);"><?= e($t['name']) ?></strong>
                                <div style="font-size:0.75rem;color:var(--text-tertiary);">UUID: <?= e($t['id']) ?></div>
                            </div>
                        </td>
                        <td>
                            <code style="font-size:0.8125rem;"><?= e($t['slug']) ?>.localhost</code>
                        </td>
                        <td>
                            <span class="badge badge-neutral"><?= e($t['industry']) ?></span>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?= ucfirst(e($t['plan'])) ?></span>
                        </td>
                        <td>
                            <form action="/admin/tenants/<?= e($t['id']) ?>/plan" method="POST" style="display:flex;gap:0.35rem;margin:0;">
                                <?= csrf_field() ?>
                                <select name="plan" class="form-select" style="width:auto;padding:0.25rem 0.5rem;font-size:0.75rem;">
                                    <option value="trial" <?= ($t['plan'] === 'trial') ? 'selected' : '' ?>>Trial</option>
                                    <option value="starter" <?= ($t['plan'] === 'starter') ? 'selected' : '' ?>>Starter</option>
                                    <option value="growth" <?= ($t['plan'] === 'growth') ? 'selected' : '' ?>>Growth</option>
                                    <option value="scale" <?= ($t['plan'] === 'scale') ? 'selected' : '' ?>>Scale</option>
                                </select>
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">Update</button>
                            </form>
                        </td>
                        <td>
                            <form action="/admin/tenants/<?= e($t['id']) ?>/impersonate" method="POST" style="margin:0;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">
                                    Impersonate Owner
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
