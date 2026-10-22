<?php $this->layout('layouts/app'); ?>

<div style="max-width:720px;margin:0 auto;">
    <div style="margin-bottom:1.5rem;">
        <a href="/leads" style="font-size:0.8125rem;color:var(--text-tertiary);">&larr; Back to Leads</a>
        <h1 class="text-title-2" style="margin-top:0.5rem;">Add New Lead / Enquiry</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;">Manual entry for phone-ins, direct referrals, or walk-ins.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="/leads" method="POST">
                <?= csrf_field() ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="name">Lead / Contact Name *</label>
                        <input type="text" class="form-input" id="name" name="name" required placeholder="e.g. Arthur Pendelton">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company">Company / Organization</label>
                        <input type="text" class="form-input" id="company" name="company" placeholder="e.g. Acme Corp (optional)">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" placeholder="client@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" class="form-input" id="phone" name="phone" placeholder="+44 7700 900123">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="service">Service / Matter Type *</label>
                        <select class="form-select" id="service" name="service" required>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= e($srv) ?>"><?= e($srv) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="value">Estimated Value (£)</label>
                        <input type="number" step="0.01" class="form-input" id="value" name="value" placeholder="e.g. 3500">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="pipeline_id">Pipeline *</label>
                        <select class="form-select" id="pipeline_id" name="pipeline_id" required>
                            <?php foreach ($pipelines as $p): ?>
                                <option value="<?= e($p['id']) ?>" <?= ($selectedPipelineId === $p['id'] || $p['is_default']) ? 'selected' : '' ?>>
                                    <?= e($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="assigned_to">Assignee (Optional)</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="">Auto-route (Round-robin)</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= e($u['id']) ?>"><?= e($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="source">Source</label>
                    <select class="form-select" id="source" name="source">
                        <option value="manual">Manual Entry / Walk-in</option>
                        <option value="referral">Client Referral</option>
                        <option value="callback">Phone Callback Request</option>
                        <option value="web_form">Web Form</option>
                        <option value="email">Direct Email</option>
                    </select>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-default);">
                    <a href="/leads" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Lead & Score</button>
                </div>
            </form>
        </div>
    </div>
</div>
