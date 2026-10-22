<?php $this->layout('layouts/app'); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 class="text-title-2">Intake Forms & Widgets</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.25rem;">
            Customizable capture forms, website embed codes, and floating callback widgets.
        </p>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Form Name</th>
                    <th>Hosted Slug</th>
                    <th>Fields</th>
                    <th>Submissions</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($forms)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:3rem 1rem;color:var(--text-tertiary);">
                            No intake forms created yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($forms as $f): ?>
                        <?php
                        $fields = is_string($f['fields']) ? json_decode($f['fields'], true) : ($f['fields'] ?? []);
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <strong style="color:var(--text-primary);"><?= e($f['name']) ?></strong>
                                    <div style="font-size:0.75rem;color:var(--text-tertiary);"><?= e($f['description']) ?></div>
                                </div>
                            </td>
                            <td>
                                <a href="/f/<?= e($f['slug']) ?>" target="_blank" style="font-family:var(--font-mono);font-size:0.8125rem;">
                                    /f/<?= e($f['slug']) ?> &nearr;
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-neutral"><?= count($fields) ?> fields</span>
                            </td>
                            <td>
                                <strong><?= (int) ($f['submissions_count'] ?? 0) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?= $f['is_active'] ? 'success' : 'neutral' ?>">
                                    <?= $f['is_active'] ? 'Active' : 'Draft' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.5rem;">
                                    <a href="/forms/<?= e($f['id']) ?>/embed" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">
                                        Get Embed Code
                                    </a>
                                    <a href="/f/<?= e($f['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">
                                        Preview &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
