<?php $this->layout('layouts/app'); ?>

<div style="margin-bottom:1.5rem;">
    <h1 class="text-title-2">Subscription & Plan Limits</h1>
    <p style="color:var(--text-secondary);font-size:0.875rem;">Manage your subscription tier, usage quotas, and invoices.</p>
</div>

<div class="tabs" style="margin-bottom:1.5rem;">
    <a href="/settings" class="tab">General</a>
    <a href="/settings/team" class="tab">Team & Roles</a>
    <a href="/settings/compliance" class="tab">Compliance & Audit Log</a>
    <a href="/settings/billing" class="tab active">Plans & Billing</a>
</div>

<?php if (!empty($upgraded)): ?>
    <div class="toast toast-success show" style="margin-bottom:1.5rem;position:static;max-width:none;transform:none;">
        <span class="text-body-medium">Successfully updated to the <?= ucfirst(e($upgraded)) ?> Plan! Quotas have been upgraded.</span>
    </div>
<?php endif; ?>

<!-- Current Plan & Quota Cards -->
<div class="card" style="margin-bottom:2rem;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <span style="font-size:0.75rem;color:var(--text-tertiary);text-transform:uppercase;">Current Plan</span>
            <h2 class="text-title-2" style="margin-top:0.25rem;"><?= ucfirst(e($currentPlan)) ?> Tier</h2>
        </div>
        <form action="/settings/billing/portal" method="POST" style="margin:0;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-sm">Stripe Customer Portal &rarr;</button>
        </form>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:1.5rem;">
            <div>
                <div style="font-size:0.8125rem;color:var(--text-tertiary);margin-bottom:0.25rem;">Active Users Quota</div>
                <div style="font-size:1.5rem;font-weight:700;">
                    <?= $usage['users'] ?> / <?= $limits['users'] ?? '10' ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:0.25rem;">Seats occupied</div>
            </div>

            <div>
                <div style="font-size:0.8125rem;color:var(--text-tertiary);margin-bottom:0.25rem;">Monthly Lead Ingestion</div>
                <div style="font-size:1.5rem;font-weight:700;">
                    <?= number_format($usage['leads_month']) ?> / <?= number_format($limits['leads_per_month'] ?? 1500) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:0.25rem;">Resets on 1st of month</div>
            </div>

            <div>
                <div style="font-size:0.8125rem;color:var(--text-tertiary);margin-bottom:0.25rem;">Automations Installed</div>
                <div style="font-size:1.5rem;font-weight:700;">
                    <?= $usage['automations'] ?> / <?= ($limits['automations'] ?? 25) === -1 ? 'Unlimited' : ($limits['automations'] ?? 25) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:0.25rem;">Active recipes</div>
            </div>
        </div>
    </div>
</div>

<!-- Switch / Upgrade Plans -->
<h3 class="text-title-3" style="margin-bottom:1rem;">Upgrade or Change Plan</h3>
<div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:1.5rem;">
    <?php foreach ($plans as $pKey => $p): ?>
        <div class="pricing-card <?= ($pKey === $currentPlan) ? 'popular' : '' ?>">
            <h4 class="text-title-3"><?= e($p['name']) ?></h4>
            <div style="display:flex;align-items:baseline;gap:0.25rem;margin:0.75rem 0 1rem;">
                <span style="font-size:2rem;font-weight:700;font-family:var(--font-heading);">£<?= $p['price_monthly'] ?></span>
                <span style="color:var(--text-tertiary);font-size:0.8125rem;">/mo</span>
            </div>
            <ul style="list-style:none;padding:0;margin:0 0 1.5rem;display:flex;flex-direction:column;gap:0.5rem;font-size:0.8125rem;flex:1;">
                <li><strong><?= $p['limits']['users'] ?></strong> Users</li>
                <li><strong><?= number_format($p['limits']['leads_per_month']) ?></strong> Leads / month</li>
                <li><strong><?= $p['limits']['automations'] === -1 ? 'Unlimited' : $p['limits']['automations'] ?></strong> Automations</li>
                <?php foreach ($p['features'] as $f): ?>
                    <li style="color:var(--text-secondary);">✓ <?= e($f) ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if ($pKey === $currentPlan): ?>
                <button type="button" class="btn btn-secondary" disabled style="width:100%;">Current Plan</button>
            <?php else: ?>
                <form action="/settings/billing/checkout" method="POST" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan" value="<?= e($pKey) ?>">
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        Switch to <?= e($p['name']) ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
