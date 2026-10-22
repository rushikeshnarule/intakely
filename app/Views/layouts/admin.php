<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Super-Admin Console — Intakely') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        .admin-nav {
            background: #111827;
            color: #ffffff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-nav a { color: #d1d5db; text-decoration: none; font-size: 0.875rem; font-weight: 500; }
        .admin-nav a:hover, .admin-nav a.active { color: #ffffff; }
    </style>
</head>
<body style="background:var(--bg-full);">
    <header class="admin-nav">
        <div style="display:flex;align-items:center;gap:1.5rem;">
            <a href="/admin" style="font-weight:700;font-size:1.125rem;color:white;display:flex;align-items:center;gap:0.5rem;">
                <span style="background:#dc2626;color:white;padding:2px 8px;border-radius:4px;font-size:0.75rem;text-transform:uppercase;">Admin</span>
                <span>Intakely HQ</span>
            </a>
            <div style="display:flex;gap:1.25rem;">
                <a href="/admin" class="<?= ($activeNav ?? '') === 'admin_dashboard' ? 'active' : '' ?>">Overview</a>
                <a href="/admin/tenants" class="<?= ($activeNav ?? '') === 'admin_tenants' ? 'active' : '' ?>">Tenants</a>
                <a href="/admin/jobs" class="<?= ($activeNav ?? '') === 'admin_jobs' ? 'active' : '' ?>">Background Jobs</a>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:1rem;">
            <span style="font-size:0.8125rem;color:#9ca3af;">Platform Operator</span>
            <a href="/pipeline" class="btn btn-secondary btn-sm" style="color:var(--text-primary);">Exit to App &rarr;</a>
        </div>
    </header>

    <main style="max-width:1140px;margin:2rem auto;padding:0 1.5rem;">
        <?php if ($msg = old_flash('success')): ?>
            <div class="toast toast-success show" style="margin-bottom:1.5rem;position:static;max-width:none;transform:none;">
                <span class="text-body-medium"><?= e($msg) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($msg = old_flash('error')): ?>
            <div class="toast toast-error show" style="margin-bottom:1.5rem;position:static;max-width:none;transform:none;">
                <span class="text-body-medium"><?= e($msg) ?></span>
            </div>
        <?php endif; ?>

        <?= $this->yield('content') ?>
    </main>

    <script src="/js/app.js"></script>
</body>
</html>
