<?php $this->layout('layouts/marketing'); ?>

<section class="hero" style="padding-bottom: 2rem;">
    <h1>Transparent, ROI-focused pricing</h1>
    <p>Choose the right plan for your practice size. All plans include 14 days free trial, no credit card required.</p>
</section>

<section style="max-width:1100px;margin:2rem auto;padding:0 2rem;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:2rem;align-items:stretch;">
        <?php foreach ($plans as $key => $plan): ?>
            <div class="pricing-card <?= !empty($plan['popular']) ? 'popular' : '' ?>">
                <?php if (!empty($plan['popular'])): ?>
                    <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--accent-600);color:white;font-size:0.6875rem;font-weight:700;padding:0.25rem 0.75rem;border-radius:var(--radius-full);text-transform:uppercase;letter-spacing:0.05em;">
                        Most Popular
                    </div>
                <?php endif; ?>

                <h3 class="text-title-2" style="margin-bottom:0.5rem;"><?= e($plan['name']) ?></h3>
                <div style="display:flex;align-items:baseline;gap:0.25rem;margin:1rem 0 1.5rem;">
                    <span style="font-size:2.5rem;font-weight:700;font-family:var(--font-heading);">£<?= $plan['price_monthly'] ?></span>
                    <span style="color:var(--text-tertiary);font-size:0.875rem;">/ month</span>
                </div>

                <ul style="list-style:none;padding:0;margin:0 0 2rem;display:flex;flex-direction:column;gap:0.75rem;flex:1;">
                    <li style="display:flex;gap:0.5rem;align-items:center;font-size:0.875rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <strong><?= $plan['limits']['users'] ?> Team Users</strong>
                    </li>
                    <li style="display:flex;gap:0.5rem;align-items:center;font-size:0.875rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Up to <strong><?= number_format($plan['limits']['leads_per_month']) ?></strong> leads/mo</span>
                    </li>
                    <li style="display:flex;gap:0.5rem;align-items:center;font-size:0.875rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong><?= $plan['limits']['automations'] === -1 ? 'Unlimited' : $plan['limits']['automations'] ?></strong> active automations</span>
                    </li>
                    <li style="display:flex;gap:0.5rem;align-items:center;font-size:0.875rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong><?= number_format($plan['limits']['ai_credits']) ?></strong> AI credits/mo</span>
                    </li>
                    <?php foreach ($plan['features'] as $feat): ?>
                        <li style="display:flex;gap:0.5rem;align-items:center;font-size:0.875rem;color:var(--text-secondary);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span><?= e($feat) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <a href="/register?plan=<?= e($key) ?>" class="btn <?= !empty($plan['popular']) ? 'btn-primary' : 'btn-secondary' ?>" style="width:100%;text-align:center;">
                    Start 14-Day Free Trial
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
