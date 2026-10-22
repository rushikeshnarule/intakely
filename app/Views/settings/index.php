<?php $this->layout('layouts/app'); ?>

<div style="margin-bottom:1.5rem;">
    <h1 class="text-title-2">Practice & Brand Settings</h1>
    <p style="color:var(--text-secondary);font-size:0.875rem;">Manage brand identities, business hours, and operational defaults.</p>
</div>

<!-- Settings Navigation Submenu -->
<div class="tabs" style="margin-bottom:1.5rem;">
    <a href="/settings" class="tab active">General</a>
    <a href="/settings/team" class="tab">Team & Roles</a>
    <a href="/settings/compliance" class="tab">Compliance & Audit Log</a>
    <a href="/settings/billing" class="tab">Plans & Billing</a>
</div>

<div class="card" style="max-width:680px;">
    <div class="card-body">
        <form action="/settings" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="business_name">Practice / Business Name</label>
                <input type="text" class="form-input" id="business_name" name="business_name" value="<?= e($settings['business_name'] ?? $tenant['name']) ?>" required>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="form-label" for="email_from_name">Email "From" Name</label>
                    <input type="text" class="form-input" id="email_from_name" name="email_from_name" value="<?= e($settings['email_from_name'] ?? $tenant['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email_from_address">Email "From" Address</label>
                    <input type="email" class="form-input" id="email_from_address" name="email_from_address" value="<?= e($settings['email_from_address'] ?? '') ?>" placeholder="enquiries@example.com">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="form-label" for="timezone">Timezone</label>
                    <select class="form-select" id="timezone" name="timezone">
                        <option value="Europe/London" <?= (($settings['timezone'] ?? '') === 'Europe/London') ? 'selected' : '' ?>>Europe/London (GMT/BST)</option>
                        <option value="America/New_York" <?= (($settings['timezone'] ?? '') === 'America/New_York') ? 'selected' : '' ?>>America/New_York (EST)</option>
                        <option value="America/Los_Angeles" <?= (($settings['timezone'] ?? '') === 'America/Los_Angeles') ? 'selected' : '' ?>>America/Los_Angeles (PST)</option>
                        <option value="Europe/Paris" <?= (($settings['timezone'] ?? '') === 'Europe/Paris') ? 'selected' : '' ?>>Europe/Paris (CET)</option>
                        <option value="Australia/Sydney" <?= (($settings['timezone'] ?? '') === 'Australia/Sydney') ? 'selected' : '' ?>>Australia/Sydney (AEST)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="currency">Currency</label>
                    <select class="form-select" id="currency" name="currency">
                        <option value="GBP" <?= (($settings['currency'] ?? '') === 'GBP') ? 'selected' : '' ?>>GBP (£)</option>
                        <option value="USD" <?= (($settings['currency'] ?? '') === 'USD') ? 'selected' : '' ?>>USD ($)</option>
                        <option value="EUR" <?= (($settings['currency'] ?? '') === 'EUR') ? 'selected' : '' ?>>EUR (€)</option>
                        <option value="AUD" <?= (($settings['currency'] ?? '') === 'AUD') ? 'selected' : '' ?>>AUD ($)</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:1.5rem;display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
