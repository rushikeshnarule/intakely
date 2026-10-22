<?php $this->layout('layouts/app'); ?>

<div style="max-width:800px;margin:0 auto;">
    <div style="margin-bottom:1.5rem;">
        <a href="/forms" style="font-size:0.8125rem;color:var(--text-tertiary);">&larr; Back to Forms</a>
        <h1 class="text-title-2" style="margin-top:0.5rem;">Embed: <?= e($form['name']) ?></h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;">
            Copy-paste snippets to embed this intake form into your website, WordPress, or client portal.
        </p>
    </div>

    <!-- 1. Direct Hosted URL -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h2 class="text-title-3" style="font-size:1rem;">1. Direct Hosted Page URL</h2>
            <a href="<?= e($formUrl) ?>" target="_blank" class="btn btn-secondary btn-sm">Open Page &nearr;</a>
        </div>
        <div class="card-body">
            <p style="font-size:0.8125rem;color:var(--text-secondary);margin-bottom:0.75rem;">
                Send this link directly in marketing campaigns, Google Ads sitelinks, or email signatures:
            </p>
            <div style="display:flex;gap:0.5rem;">
                <input type="text" class="form-input" style="font-family:var(--font-mono);font-size:0.8125rem;" value="<?= e($formUrl) ?>" readonly id="url-input">
                <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('url-input').value); Toast.success('Copied URL!');">
                    Copy
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Responsive Iframe Embed -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <h2 class="text-title-3" style="font-size:1rem;">2. Responsive Iframe Embed Code</h2>
            <span class="badge badge-success">Any Website / CMS</span>
        </div>
        <div class="card-body">
            <p style="font-size:0.8125rem;color:var(--text-secondary);margin-bottom:0.75rem;">
                Paste this HTML code block anywhere on your website (e.g. your Contact page):
            </p>
            <textarea class="form-textarea" rows="4" style="font-family:var(--font-mono);font-size:0.8125rem;" readonly id="iframe-code">&lt;iframe src="<?= e($formUrl) ?>" width="100%" height="700" style="border:none;border-radius:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.06);" title="<?= e($form['name']) ?>"&gt;&lt;/iframe&gt;</textarea>
            <div style="margin-top:0.75rem;display:flex;justify-content:flex-end;">
                <button type="button" class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('iframe-code').value); Toast.success('Copied Iframe Code!');">
                    Copy Iframe Code
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Floating "Request a Call Back" Widget -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-title-3" style="font-size:1rem;">3. Floating Callback Widget (1-Line Script)</h2>
            <span class="badge badge-primary">High Conversion</span>
        </div>
        <div class="card-body">
            <p style="font-size:0.8125rem;color:var(--text-secondary);margin-bottom:0.75rem;">
                Adds a subtle floating phone widget to the bottom right of your site that captures instant callback requests:
            </p>
            <textarea class="form-textarea" rows="3" style="font-family:var(--font-mono);font-size:0.8125rem;" readonly id="widget-code">&lt;script src="<?= e($appUrl) ?>/js/widget.js" data-tenant="<?= e($tenantId) ?>" async&gt;&lt;/script&gt;</textarea>
            <div style="margin-top:0.75rem;display:flex;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('widget-code').value); Toast.success('Copied Widget Script!');">
                    Copy Script Tag
                </button>
            </div>
        </div>
    </div>
</div>
