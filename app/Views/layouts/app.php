<?php
$tenant = $_SESSION['_tenant'] ?? ['name' => 'Apex Law LLP', 'slug' => 'apex-law'];
$user = $_SESSION['_user'] ?? ['name' => 'Eleanor Vance', 'role' => 'owner', 'email' => 'eleanor@example.com'];
$firstName = explode(' ', $user['name'])[0] ?? 'Counsel';
$roleTitle = match(strtolower($user['role'] ?? '')) {
    'owner' => 'Managing Partner',
    'admin' => 'Senior Partner',
    'manager' => 'Lead Counsel',
    'agent' => 'Associate Counsel',
    default => 'Legal Counsel'
};

$quickPipelines = [];
$quickUsers = [];
$quickServices = [
    'Commercial Litigation',
    'Conveyancing & Real Estate',
    'Corporate Advisory',
    'Probate & Estate Administration',
    'Family & Matrimonial Law',
    'Regulatory & Compliance',
    'Immigration & Cross-Border',
    'General Retainer'
];
try {
    $db = \App\Core\Container::getInstance()->get(\App\Core\Database::class);
    $tenantId = $tenant['id'] ?? ($_SESSION['_tenant_id'] ?? null);
    if ($tenantId) {
        $quickPipelines = $db->fetchAll('SELECT id, name FROM pipelines WHERE tenant_id = $1 ORDER BY sort_order ASC', [$tenantId]);
        $quickUsers = $db->fetchAll('SELECT id, name FROM users WHERE tenant_id = $1 AND is_active = true ORDER BY name ASC', [$tenantId]);
    }
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($title ?? 'Intakely — Law Firm Management & Intake') ?></title>
    <!-- Default to Executive Obsidian Dark Theme unless explicitly set to light -->
    <script>(function(){try{const t=localStorage.getItem('intakely-theme');if(t!=='light'){document.documentElement.classList.add('dark');}}catch(e){document.documentElement.classList.add('dark');}})();</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <!-- SortableJS from cdnjs for Kanban Board drag & drop -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <!-- Chart.js from cdnjs for Analytics -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        .search-wrapper { position: relative; width: 340px; }
        .search-results {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0;
            background: var(--bg-primary); border: 1px solid var(--border-default);
            border-radius: var(--radius-xl); box-shadow: var(--shadow-lg);
            max-height: 380px; overflow-y: auto; z-index: var(--z-popover);
        }
        .search-item {
            padding: 0.65rem 0.95rem; border-bottom: 1px solid var(--border-default);
            cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        }
        .search-item:hover { background: var(--bg-secondary); }
    </style>
</head>
<body data-authenticated="true">
    <div class="app-layout">
        <!-- Sidebar Navigation (Amirul Islam Law Firm Style) -->
        <aside class="app-sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-crest">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v18M6 8l6-5 6 5M3 14l3-6 3 6a3 3 0 0 1-6 0zm12 0l3-6 3 6a3 3 0 0 1-6 0z"/>
                    </svg>
                </div>
                <div class="truncate">
                    <div class="sidebar-brand-name truncate"><?= e($tenant['name']) ?></div>
                    <div class="sidebar-brand-sub">Practice Management</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Core Practice</div>
                    <a href="/dashboard" class="sidebar-link <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="/pipeline" class="sidebar-link <?= ($activeNav ?? '') === 'pipeline' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="11" rx="1"/></svg>
                        <span>Case Pipeline</span>
                    </a>
                    <a href="/leads" class="sidebar-link <?= ($activeNav ?? '') === 'leads' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m8-10a4 4 0 100-8 4 4 0 000 8zm14 10v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg>
                        <span>Matters & Clients</span>
                    </a>
                    <a href="/appointments" class="sidebar-link <?= ($activeNav ?? '') === 'appointments' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Hearings & Calendar</span>
                    </a>
                    <a href="/tasks" class="sidebar-link <?= ($activeNav ?? '') === 'tasks' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                        <span>Action Tasks</span>
                    </a>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-section-title">Automations & Intake</div>
                    <a href="/forms" class="sidebar-link <?= ($activeNav ?? '') === 'forms' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <span>Intake Form Builder</span>
                    </a>
                    <a href="/automations" class="sidebar-link <?= ($activeNav ?? '') === 'automations' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        <span>Workflows & Drips</span>
                    </a>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-section-title">Insights & Management</div>
                    <a href="/analytics" class="sidebar-link <?= ($activeNav ?? '') === 'analytics' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        <span>Billing & Analytics</span>
                    </a>
                    <a href="/settings" class="sidebar-link <?= ($activeNav ?? '') === 'settings' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        <span>Firm & Permissions</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user-card">
                    <div class="sidebar-user-avatar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        <span class="user-status-dot" title="Active in Chambers"></span>
                    </div>
                    <div class="truncate" style="flex:1;">
                        <div class="sidebar-user-name truncate"><?= e($user['name']) ?></div>
                        <div class="sidebar-user-role truncate"><?= e($roleTitle) ?></div>
                    </div>
                    <form action="/logout" method="POST" style="margin:0;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-icon btn-ghost" title="Sign Out of Chambers" aria-label="Sign Out">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="app-main">
            <header class="app-header">
                <button id="sidebar-toggle" class="btn-icon btn-ghost" style="margin-right:1rem;" aria-label="Toggle Sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>

                <!-- Greeting Breadcrumb / Title -->
                <div class="header-greeting-box">
                    <div class="header-greeting-title">Good morning, <?= e($firstName) ?></div>
                    <div class="header-greeting-subtitle">
                        <span>Apex Chambers</span>
                        <span class="dot-separator">•</span>
                        <span><?= date('l, j F') ?></span>
                    </div>
                </div>

                <!-- Global Command Search -->
                <div class="search-wrapper" style="margin-left: 2rem;">
                    <input type="text" id="global-search" class="form-input" placeholder="Search cases, clients, legal briefs..." style="padding-left:2.25rem;padding-right:2.5rem;border-radius:var(--radius-full);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--text-tertiary);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <kbd class="search-kbd">⌘K</kbd>
                    <div id="search-results" class="search-results hidden"></div>
                </div>

                <div style="margin-left:auto;display:flex;align-items:center;gap:0.75rem;">
                    <!-- Live Chambers SSE Sync Status Indicator -->
                    <div class="live-sync-pill">
                        <span class="live-sync-dot"></span>
                        <span class="live-sync-text">Live Sync</span>
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button id="dark-mode-toggle" class="btn-icon btn-ghost" title="Toggle Light/Dark Theme" aria-label="Toggle Theme">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    </button>

                    <!-- Quick Add Lead / Matter Button -->
                    <button type="button" onclick="openNewMatterModal()" class="btn btn-primary btn-sm" style="font-weight:600;padding:0.45rem 1rem;cursor:pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>New Matter</span>
                    </button>
                </div>
            </header>

            <div class="app-content">
                <?php if ($msg = old_flash('success')): ?>
                    <div class="toast toast-success show" style="margin-bottom:1rem;position:static;max-width:none;transform:none;">
                        <span class="text-body-medium"><?= e($msg) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($msg = old_flash('error')): ?>
                    <div class="toast toast-error show" style="margin-bottom:1rem;position:static;max-width:none;transform:none;">
                        <span class="text-body-medium"><?= e($msg) ?></span>
                    </div>
                <?php endif; ?>

                <?= $this->yield('content') ?>
            </div>
        </div>
    </div>

    <!-- Quick Add New Matter In-Page Modal -->
    <div id="modal-new-matter" class="modal-backdrop" onclick="if(event.target === this) closeNewMatterModal();">
        <div class="modal" style="max-width: 620px; border-radius: var(--radius-2xl); overflow: hidden;">
            <div class="modal-header" style="background: var(--bg-secondary); padding: 1.25rem 1.75rem; border-bottom: 1px solid var(--border-default); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(59, 130, 246, 0.15); color: #3B82F6; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    </div>
                    <div>
                        <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--text-primary); margin: 0; line-height: 1.2;">Register New Matter</h2>
                        <p style="font-size: 0.75rem; color: var(--text-tertiary); margin: 0.15rem 0 0;">Apex Chambers Matter Intake Registry</p>
                    </div>
                </div>
                <button type="button" onclick="closeNewMatterModal()" class="btn-icon btn-ghost" aria-label="Close modal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form id="form-new-matter" onsubmit="handleNewMatterSubmit(event)" style="margin: 0;">
                <div class="modal-body" style="padding: 1.5rem 1.75rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div id="new-matter-error" style="display: none; padding: 0.75rem 1rem; border-radius: var(--radius-md); background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 0.8125rem;"></div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Matter / Client Name <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" name="name" id="new-matter-name" class="form-input" placeholder="e.g. Sterling Holdings Ltd" required style="width: 100%;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Corporate Entity / Company
                            </label>
                            <input type="text" name="company" class="form-input" placeholder="e.g. Apex Global Corp" style="width: 100%;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Primary Email
                            </label>
                            <input type="email" name="email" class="form-input" placeholder="counsel@sterling.co.uk" style="width: 100%;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Contact Telephone
                            </label>
                            <input type="tel" name="phone" class="form-input" placeholder="+44 20 7946 0991" style="width: 100%;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Practice Area / Service
                            </label>
                            <select name="service" class="form-select" style="width: 100%;">
                                <?php foreach ($quickServices as $srv): ?>
                                    <option value="<?= e($srv) ?>"><?= e($srv) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Target Pipeline
                            </label>
                            <select name="pipeline_id" id="new-matter-pipeline" class="form-select" style="width: 100%;">
                                <?php foreach ($quickPipelines as $p): ?>
                                    <option value="<?= e($p['id']) ?>"><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Estimated Retainer (£)
                            </label>
                            <input type="number" name="value" class="form-input" placeholder="12500" min="0" step="100" style="width: 100%;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Urgency / Priority
                            </label>
                            <select name="priority" class="form-select" style="width: 100%;">
                                <option value="normal">Normal Priority</option>
                                <option value="high">🔥 High / Court Deadline</option>
                                <option value="low">Low Priority</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Assigned Counsel
                            </label>
                            <select name="assigned_to" class="form-select" style="width: 100%;">
                                <option value="">Auto-Route (Chambers Rota)</option>
                                <?php foreach ($quickUsers as $u): ?>
                                    <option value="<?= e($u['id']) ?>"><?= e($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; display: block; color: var(--text-primary);">
                                Inbound Channel
                            </label>
                            <select name="source" class="form-select" style="width: 100%;">
                                <option value="manual">Manual Chambers Intake</option>
                                <option value="web_form">Firm Website Portal</option>
                                <option value="referral">Partner Referral</option>
                                <option value="phone">Direct Telephone Inquiry</option>
                                <option value="corporate">General Counsel Inbound</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="background: var(--bg-secondary); padding: 1rem 1.75rem; border-top: 1px solid var(--border-default); display: flex; align-items: center; justify-content: space-between;">
                    <a href="/leads/create" style="font-size: 0.8125rem; color: var(--accent-600); text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                        <span>Open Full Compliance Intake Form</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>

                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" onclick="closeNewMatterModal()" class="btn btn-secondary btn-sm" style="font-weight: 500;">
                            Cancel
                        </button>
                        <button type="submit" id="btn-submit-new-matter" class="btn btn-primary btn-sm" style="font-weight: 600; min-width: 120px;">
                            <span>Register Matter</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/app.js"></script>
    <script>
    window.openNewMatterModal = function(pipelineId) {
        const modal = document.getElementById('modal-new-matter');
        if (!modal) return;
        if (pipelineId) {
            const pipeSelect = document.getElementById('new-matter-pipeline');
            if (pipeSelect) pipeSelect.value = pipelineId;
        }
        modal.classList.add('active');
        setTimeout(() => {
            const nameInput = document.getElementById('new-matter-name');
            if (nameInput) nameInput.focus();
        }, 100);
    };

    window.closeNewMatterModal = function() {
        const modal = document.getElementById('modal-new-matter');
        if (modal) modal.classList.remove('active');
        const errBox = document.getElementById('new-matter-error');
        if (errBox) { errBox.style.display = 'none'; errBox.textContent = ''; }
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeNewMatterModal();
        }
    });

    window.handleNewMatterSubmit = async function(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btn-submit-new-matter');
        const errBox = document.getElementById('new-matter-error');
        if (errBox) { errBox.style.display = 'none'; errBox.textContent = ''; }

        const formData = new FormData(form);
        const payload = {};
        formData.forEach((val, key) => { payload[key] = val; });

        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span>Registering...</span>';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/leads', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.error || data.message || 'Failed to register matter.');
            }

            // Success! Close modal and refresh current view seamlessly
            closeNewMatterModal();
            form.reset();

            if (window.location.pathname.startsWith('/pipeline') || window.location.pathname.startsWith('/leads')) {
                window.location.reload();
            } else {
                if (data.lead && data.lead.id) {
                    window.location.href = '/leads/' + data.lead.id;
                } else {
                    window.location.reload();
                }
            }
        } catch (err) {
            if (errBox) {
                errBox.textContent = err.message || 'An unexpected error occurred.';
                errBox.style.display = 'block';
            } else {
                alert(err.message || 'Error registering matter');
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    };
    </script>
    <?= $this->yield('scripts') ?>
</body>
</html>
