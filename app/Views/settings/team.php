<?php $this->layout('layouts/app'); ?>

<div style="margin-bottom:1.5rem;">
    <h1 class="text-title-2">Team Members & Access Roles</h1>
    <p style="color:var(--text-secondary);font-size:0.875rem;">Manage employee access levels, departmental routing, and invites.</p>
</div>

<div class="tabs" style="margin-bottom:1.5rem;">
    <a href="/settings" class="tab">General</a>
    <a href="/settings/team" class="tab active">Team & Roles</a>
    <a href="/settings/compliance" class="tab">Compliance & Audit Log</a>
    <a href="/settings/billing" class="tab">Plans & Billing</a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start;">
    <!-- Team Members Table -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-title-3">Current Team Members</h2>
            <span class="badge badge-neutral"><?= count($users) ?> Active Users</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name & Email</th>
                        <th>Role</th>
                        <th>Team / Dept</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong style="color:var(--text-primary);"><?= e($u['name']) ?></strong>
                                    <div style="font-size:0.75rem;color:var(--text-tertiary);"><?= e($u['email']) ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= ($u['role'] === 'owner') ? 'success' : (($u['role'] === 'admin') ? 'primary' : 'neutral') ?>" style="text-transform:capitalize;">
                                    <?= e($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;"><?= e($u['department'] ?: $u['team'] ?: 'General') ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $u['is_active'] ? 'success' : 'neutral' ?>">
                                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invite Team Member Form -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-title-3" style="font-size:1rem;">Invite Team Member</h2>
        </div>
        <div class="card-body">
            <form action="/settings/team/invite" method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Full Name *</label>
                    <input type="text" class="form-input" id="name" name="name" required placeholder="e.g. Sarah Jenkins">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Work Email *</label>
                    <input type="email" class="form-input" id="email" name="email" required placeholder="sarah@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Role / Permission Level</label>
                    <select class="form-select" id="role" name="role">
                        <option value="agent">Agent (View & Edit Leads, Add Notes)</option>
                        <option value="manager">Manager (Stage approvals, Team reassignment)</option>
                        <option value="admin">Admin (Full settings & user management)</option>
                        <option value="viewer">Viewer (Read-only)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="department">Department / Specialty</label>
                    <input type="text" class="form-input" id="department" name="department" placeholder="e.g. Conveyancing or Private Client">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">
                    Send Team Invitation
                </button>
            </form>
        </div>
    </div>
</div>
