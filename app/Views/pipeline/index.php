<?php
$this->layout('layouts/app');

// Compute Executive HUD Metrics across all pipeline stages
$totalMatters = 0;
$totalPipelineValue = 0.0;
$highPriorityCount = 0;
$conflictClearedCount = 0;
$slaRiskCount = 0;

foreach ($stages as $stg) {
    $sId = $stg['id'];
    $sLeads = $leadsByStage[$sId] ?? [];
    foreach ($sLeads as $l) {
        $totalMatters++;
        $totalPipelineValue += (float)($l['value'] ?? 0);
        if (($l['priority'] ?? '') === 'high') $highPriorityCount++;
        if (!empty($l['sla_breached'])) $slaRiskCount++;
        
        $custom = is_string($l['custom_fields'] ?? null) ? json_decode($l['custom_fields'], true) : ($l['custom_fields'] ?? []);
        if (!empty($custom['conflict_check_passed'])) $conflictClearedCount++;
    }
}
$conflictRate = $totalMatters > 0 ? round(($conflictClearedCount / $totalMatters) * 100) : 100;
?>

<!-- Header & Pipeline Switcher -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <div style="display:flex;align-items:center;gap:1rem;">
            <h1 class="text-title-1" style="font-weight:700;color:var(--text-primary);letter-spacing:-0.02em;"><?= e($pipeline['name']) ?></h1>
            <!-- Pipeline Switcher Dropdown if multiple pipelines exist -->
            <?php if (count($pipelines) > 1): ?>
                <div class="dropdown">
                    <button class="btn btn-secondary btn-sm" data-dropdown style="font-weight:600;font-size:0.75rem;">
                        <span>Switch Pipeline</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="dropdown-menu">
                        <?php foreach ($pipelines as $p): ?>
                            <a href="/pipeline/<?= e($p['id']) ?>" class="dropdown-item <?= ($p['id'] === $pipeline['id']) ? 'active' : '' ?>">
                                <?= e($p['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.25rem;">
            <?= e($pipeline['description'] ?: 'Legal matter tracking with SRA compliance gates, SLA countdowns, and automated client progression.') ?>
        </p>
    </div>

    <!-- View Toggle & Actions -->
    <div style="display:flex;gap:0.75rem;align-items:center;">
        <div style="background:var(--bg-secondary);padding:0.25rem;border-radius:var(--radius-lg);display:flex;gap:0.25rem;border:1px solid var(--border-default);">
            <a href="/pipeline/<?= e($pipeline['id']) ?>" class="btn btn-sm btn-secondary" style="box-shadow:var(--shadow-xs);font-weight:600;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="11" rx="1"/></svg>
                <span>Board View</span>
            </a>
            <a href="/leads?pipeline=<?= e($pipeline['id']) ?>" class="btn btn-sm btn-ghost" style="font-weight:500;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <span>Table View</span>
            </a>
        </div>
        <button type="button" onclick="openNewMatterModal('<?= e($pipeline['id']) ?>')" class="btn btn-primary btn-sm" style="font-weight:600;padding:0.45rem 1rem;cursor:pointer;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>+ New Matter</span>
        </button>
    </div>
</div>

<!-- Executive Metrics HUD (Heads-Up Display) -->
<div class="executive-hud">
    <div class="hud-metric-card hud-accent">
        <div class="hud-metric-info">
            <span class="hud-metric-label">Pipeline Retainer Value</span>
            <span class="hud-metric-value">£<?= number_format($totalPipelineValue) ?></span>
            <span class="hud-metric-sub">
                <span style="color:var(--text-success);font-weight:600;">▲ 18.4%</span> across active retainer stages
            </span>
        </div>
        <div class="hud-metric-icon" style="background:rgba(59, 130, 246, 0.12);color:#3B82F6;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
    </div>

    <div class="hud-metric-card hud-purple">
        <div class="hud-metric-info">
            <span class="hud-metric-label">Active Chambers Caseload</span>
            <span class="hud-metric-value"><?= $totalMatters ?> Matters</span>
            <span class="hud-metric-sub">
                <span style="color:#A78BFA;font-weight:600;">● <?= count($stages) ?> stages</span> in active workflow
            </span>
        </div>
        <div class="hud-metric-icon" style="background:rgba(139, 92, 246, 0.12);color:#8B5CF6;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="11" rx="1"/></svg>
        </div>
    </div>

    <div class="hud-metric-card hud-success">
        <div class="hud-metric-info">
            <span class="hud-metric-label">SRA Conflict Clearance</span>
            <span class="hud-metric-value"><?= $conflictRate ?>%</span>
            <span class="hud-metric-sub">
                <span style="color:var(--text-success);font-weight:600;">⚖️ <?= $conflictClearedCount ?> of <?= $totalMatters ?></span> cleared
            </span>
        </div>
        <div class="hud-metric-icon" style="background:rgba(16, 185, 129, 0.12);color:#10B981;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
    </div>

    <div class="hud-metric-card hud-warning">
        <div class="hud-metric-info">
            <span class="hud-metric-label">Court Urgency / SLA Risk</span>
            <span class="hud-metric-value"><?= $highPriorityCount ?> High Priority</span>
            <span class="hud-metric-sub">
                <span style="color:<?= $slaRiskCount > 0 ? '#EF4444' : 'var(--text-success)' ?>;font-weight:600;">⚠️ <?= $slaRiskCount ?> SLA overdue</span>
            </span>
        </div>
        <div class="hud-metric-icon" style="background:rgba(245, 158, 11, 0.12);color:#F59E0B;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>
</div>

<!-- Filters Toolbar & Quick Chips -->
<div class="board-filter-toolbar">
    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
        <!-- Live Instant Board Filter Input -->
        <div style="position:relative;width:260px;">
            <input type="text" id="board-search" class="form-input" placeholder="Filter matters, clients, counsel..." style="padding-left:2.2rem;padding-top:0.4rem;padding-bottom:0.4rem;font-size:0.8125rem;border-radius:var(--radius-full);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--text-tertiary);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>

        <!-- Quick Filter Pills -->
        <div style="display:flex;gap:0.4rem;align-items:center;flex-wrap:wrap;">
            <button type="button" class="filter-pill active" data-filter="all">All Matters</button>
            <button type="button" class="filter-pill" data-filter="high">🔥 High Priority</button>
            <button type="button" class="filter-pill" data-filter="breached">⚠️ SLA Risk</button>
            <button type="button" class="filter-pill" data-filter="high_value">💎 VIP (£10k+)</button>
            <button type="button" class="filter-pill" data-filter="conflict">⚖️ Conflict Cleared</button>
        </div>
    </div>

    <!-- Server Filters -->
    <form method="GET" action="/pipeline/<?= e($pipeline['id']) ?>" style="display:flex;gap:0.5rem;align-items:center;margin:0;">
        <select name="assignee" class="form-select" style="width:auto;padding:0.3rem 0.75rem;font-size:0.75rem;border-radius:var(--radius-md);" onchange="this.form.submit()">
            <option value="">All Counsel</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= e($u['id']) ?>" <?= ($filterAssignee === $u['id']) ? 'selected' : '' ?>>
                    <?= e($u['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="source" class="form-select" style="width:auto;padding:0.3rem 0.75rem;font-size:0.75rem;border-radius:var(--radius-md);" onchange="this.form.submit()">
            <option value="">All Channels</option>
            <option value="web_form" <?= ($filterSource === 'web_form') ? 'selected' : '' ?>>Web Intake</option>
            <option value="callback" <?= ($filterSource === 'callback') ? 'selected' : '' ?>>Callback</option>
            <option value="google_ads" <?= ($filterSource === 'google_ads') ? 'selected' : '' ?>>Google Ads</option>
            <option value="referral" <?= ($filterSource === 'referral') ? 'selected' : '' ?>>Referral</option>
            <option value="whatsapp" <?= ($filterSource === 'whatsapp') ? 'selected' : '' ?>>WhatsApp</option>
            <option value="email" <?= ($filterSource === 'email') ? 'selected' : '' ?>>Direct Email</option>
        </select>

        <?php if ($filterPriority || $filterAssignee || $filterSource): ?>
            <a href="/pipeline/<?= e($pipeline['id']) ?>" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Kanban Board Container -->
<div class="board" id="kanban-board">
    <?php foreach ($stages as $stage): ?>
        <?php
        $stageId = $stage['id'];
        $stageLeads = $leadsByStage[$stageId] ?? [];
        $totals = $stageTotals[$stageId] ?? ['count' => 0, 'value' => 0.0];
        $stageColor = $stage['color'] ?: '#3b82f6';
        ?>
        <div class="board-column" data-stage-id="<?= e($stageId) ?>" style="border-top: 3px solid <?= e($stageColor) ?>;">
            <!-- Column Header -->
            <div class="board-column-header">
                <div>
                    <div class="board-column-title">
                        <span class="board-column-dot" style="background:<?= e($stageColor) ?>;box-shadow:0 0 10px <?= e($stageColor) ?>;"></span>
                        <span class="board-column-name"><?= e($stage['name']) ?></span>
                        <span class="board-column-count" id="count-<?= e($stageId) ?>"><?= $totals['count'] ?></span>
                    </div>
                </div>
                <div class="board-column-value" id="value-<?= e($stageId) ?>">
                    £<?= number_format($totals['value']) ?>
                </div>
            </div>

            <!-- Cards Container (Sortable) -->
            <div class="board-column-cards sortable-cards" data-stage-id="<?= e($stageId) ?>" id="cards-<?= e($stageId) ?>">
                <?php foreach ($stageLeads as $lead): ?>
                    <?php
                    $leadTags = is_string($lead['tags']) ? json_decode($lead['tags'], true) : ($lead['tags'] ?? []);
                    $custom = is_string($lead['custom_fields'] ?? null) ? json_decode($lead['custom_fields'], true) : ($lead['custom_fields'] ?? []);
                    $conflictClear = !empty($custom['conflict_check_passed']);
                    $amlVerified = !empty($custom['id_aml_verified']);
                    $isBreached = !empty($lead['sla_breached']);

                    // Practice area badge styling
                    $svc = strtolower($lead['service'] ?? '');
                    $badgeStyle = match(true) {
                        str_contains($svc, 'litigation') => ['bg' => 'rgba(99, 102, 241, 0.16)', 'color' => '#A5B4FC', 'border' => 'rgba(99, 102, 241, 0.35)'],
                        str_contains($svc, 'conveyancing') => ['bg' => 'rgba(14, 165, 233, 0.16)', 'color' => '#7DD3FC', 'border' => 'rgba(14, 165, 233, 0.35)'],
                        str_contains($svc, 'probate') || str_contains($svc, 'will') => ['bg' => 'rgba(168, 85, 247, 0.16)', 'color' => '#D8B4FE', 'border' => 'rgba(168, 85, 247, 0.35)'],
                        str_contains($svc, 'corporate') || str_contains($svc, 'tax') => ['bg' => 'rgba(16, 185, 129, 0.16)', 'color' => '#6EE7B7', 'border' => 'rgba(16, 185, 129, 0.35)'],
                        str_contains($svc, 'family') || str_contains($svc, 'divorce') => ['bg' => 'rgba(236, 72, 153, 0.16)', 'color' => '#F472B6', 'border' => 'rgba(236, 72, 153, 0.35)'],
                        default => ['bg' => 'rgba(59, 130, 246, 0.16)', 'color' => '#93C5FD', 'border' => 'rgba(59, 130, 246, 0.35)'],
                    };
                    ?>
                    <div class="lead-card" 
                         data-lead-id="<?= e($lead['id']) ?>" 
                         data-lead-value="<?= (float)($lead['value'] ?? 0) ?>" 
                         data-lead-priority="<?= e($lead['priority']) ?>" 
                         data-lead-breached="<?= $isBreached ? '1' : '0' ?>" 
                         data-lead-conflict="<?= $conflictClear ? '1' : '0' ?>" 
                         data-search-text="<?= strtolower(e($lead['name'] . ' ' . ($lead['company'] ?? '') . ' ' . ($lead['service'] ?? ''))) ?>" 
                         id="card-<?= e($lead['id']) ?>"
                         onclick="openMatterDrawer('<?= e($lead['id']) ?>')">
                        
                        <!-- Hover Action Buttons -->
                        <div class="lead-card-quick-actions" onclick="event.stopPropagation();">
                            <button type="button" class="btn-card-action" onclick="openMatterDrawer('<?= e($lead['id']) ?>')" title="Quick Inspect">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <a href="/leads/<?= e($lead['id']) ?>" class="btn-card-action" title="Open Full File">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                        </div>

                        <!-- Top Row: Practice Badge + Priority Indicator -->
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.6rem;">
                            <span class="matter-badge" style="background:<?= $badgeStyle['bg'] ?>;color:<?= $badgeStyle['color'] ?>;border:1px solid <?= $badgeStyle['border'] ?>;">
                                <?= e($lead['service'] ?: 'General Practice') ?>
                            </span>
                            <div style="display:flex;align-items:center;gap:0.35rem;">
                                <span class="lead-card-score" title="Intake Score: <?= $lead['score'] ?>/100">
                                    ⚡ <?= $lead['score'] ?>
                                </span>
                                <span class="lead-card-priority <?= e($lead['priority']) ?>" title="Priority: <?= e(ucfirst($lead['priority'])) ?>"></span>
                            </div>
                        </div>

                        <!-- Matter Title & Client -->
                        <div class="lead-card-header" style="margin-bottom:0.5rem;">
                            <div>
                                <span class="lead-card-name" style="text-decoration:none;display:block;">
                                    <?= e($lead['name']) ?>
                                </span>
                                <?php if (!empty($lead['company'])): ?>
                                    <div class="lead-card-company truncate"><?= e($lead['company']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Retainer Value & SLA Countdown -->
                        <div class="lead-card-meta" style="margin-bottom:0.65rem;">
                            <?php if (!empty($lead['value'])): ?>
                                <span class="lead-card-value">£<?= number_format((float)$lead['value']) ?></span>
                            <?php endif; ?>

                            <?php if ($isBreached): ?>
                                <span class="sla-timer breached">⚠️ SLA Breached</span>
                            <?php elseif (!empty($lead['sla_deadline'])): ?>
                                <span class="sla-timer ok" data-sla-deadline="<?= e($lead['sla_deadline']) ?>">
                                    Calculating SLA...
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Compliance Chips -->
                        <div style="display:flex;gap:0.35rem;align-items:center;margin-bottom:0.65rem;flex-wrap:wrap;">
                            <span class="compliance-chip <?= $conflictClear ? 'chip-passed' : 'chip-pending' ?>" id="chip-conflict-<?= e($lead['id']) ?>">
                                <?= $conflictClear ? '⚖️ Conflict Cleared' : '⚖️ Conflict Pending' ?>
                            </span>
                            <?php if ($amlVerified): ?>
                                <span class="compliance-chip chip-passed" id="chip-aml-<?= e($lead['id']) ?>">
                                    🛡️ AML Verified
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Footer: Counsel Avatar & Intake Channel -->
                        <div class="lead-card-footer">
                            <div class="lead-card-source">
                                <span><?= e(ucfirst(str_replace('_', ' ', $lead['source'] ?? 'web'))) ?></span>
                            </div>
                            <div class="lead-card-assignee" title="Assigned to <?= e($lead['assignee_name'] ?? 'Unassigned Counsel') ?>">
                                <?php if (!empty($lead['assignee_name'])): ?>
                                    <div class="lead-card-avatar">
                                        <?= strtoupper(substr($lead['assignee_name'], 0, 1)) ?>
                                    </div>
                                    <span class="truncate" style="max-width:90px;color:var(--text-secondary);"><?= e(explode(' ', $lead['assignee_name'])[0]) ?></span>
                                <?php else: ?>
                                    <span style="font-size:0.6875rem;color:var(--text-tertiary);">Unassigned</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ============================================================
     SLIDE-OVER MATTER DRAWER (Enterprise 0-Reload Quick View)
     ============================================================ -->
<div id="drawer-backdrop" class="matter-drawer-backdrop" onclick="closeMatterDrawer()"></div>
<aside id="matter-drawer" class="matter-drawer" aria-label="Matter Quick Inspector">
    <!-- Drawer Header -->
    <div class="drawer-header">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div id="drawer-practice-icon" style="width:38px;height:38px;border-radius:10px;background:rgba(59,130,246,0.15);color:#3B82F6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span id="drawer-matter-code" style="font-family:var(--font-mono);font-size:0.75rem;color:var(--text-tertiary);font-weight:600;">MAT-0000</span>
                    <span id="drawer-service-badge" class="matter-badge" style="background:rgba(59,130,246,0.15);color:#93C5FD;">General Practice</span>
                </div>
                <h2 id="drawer-matter-name" style="font-size:1.15rem;font-weight:700;color:var(--text-primary);margin:0.2rem 0 0;line-height:1.25;">Matter Name</h2>
            </div>
        </div>
        <button type="button" onclick="closeMatterDrawer()" class="btn-icon btn-ghost" aria-label="Close Drawer">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <!-- Drawer Body -->
    <div class="drawer-body">
        <!-- Retainer & Stage Quick Bar -->
        <div class="drawer-section" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:center;">
            <div>
                <span class="hud-metric-label">Retainer Value</span>
                <div id="drawer-matter-value" style="font-family:var(--font-heading);font-size:1.4rem;font-weight:700;color:var(--text-primary);">£0</div>
            </div>
            <div>
                <span class="hud-metric-label">Workflow Stage</span>
                <select id="drawer-stage-select" class="form-select" style="font-size:0.8125rem;font-weight:600;padding:0.4rem 0.65rem;" onchange="handleDrawerStageChange(this.value)">
                    <?php foreach ($stages as $s): ?>
                        <option value="<?= e($s['id']) ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- SRA Compliance Checklist Gates -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <span>SRA Compliance Gates</span>
                <span style="font-size:0.6875rem;color:var(--text-tertiary);">Regulatory Standard</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-size:0.8125rem;font-weight:600;color:var(--text-primary);">Conflict of Interest Check</div>
                        <div style="font-size:0.6875rem;color:var(--text-tertiary);">SRA Code of Conduct Rule 6.2</div>
                    </div>
                    <button type="button" id="btn-toggle-conflict" class="compliance-toggle-btn pending" onclick="handleToggleConflict()">
                        ⚖️ Check Pending
                    </button>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:0.5rem;border-top:1px solid var(--border-default);">
                    <div>
                        <div style="font-size:0.8125rem;font-weight:600;color:var(--text-primary);">Client Identity & AML Check</div>
                        <div style="font-size:0.6875rem;color:var(--text-tertiary);">Money Laundering Regulations 2017</div>
                    </div>
                    <button type="button" id="btn-toggle-aml" class="compliance-toggle-btn pending" onclick="handleToggleAml()">
                        🛡️ AML Pending
                    </button>
                </div>
            </div>
        </div>

        <!-- Client & Contact Info -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <span>Client Contact Details</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:0.6rem;font-size:0.8125rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:var(--text-tertiary);">Email Address:</span>
                    <a id="drawer-client-email" href="#" style="color:var(--text-link);font-weight:500;text-decoration:none;">counsel@client.com</a>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:var(--text-tertiary);">Telephone:</span>
                    <a id="drawer-client-phone" href="#" style="color:var(--text-primary);font-weight:500;text-decoration:none;">+44 20 7946 0000</a>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:var(--text-tertiary);">Company / Entity:</span>
                    <span id="drawer-client-company" style="color:var(--text-primary);font-weight:500;">Sterling Corp Ltd</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:var(--text-tertiary);">Assigned Counsel:</span>
                    <span id="drawer-client-assignee" style="color:var(--text-primary);font-weight:600;">Eleanor Vance</span>
                </div>
            </div>
        </div>

        <!-- Quick Activity Timeline & Note Composer -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <span>Activity Timeline & Notes</span>
            </div>
            
            <!-- Quick Note Input -->
            <form onsubmit="handleDrawerNoteSubmit(event)" style="margin-bottom:1rem;display:flex;gap:0.5rem;">
                <input type="text" id="drawer-note-input" class="form-input" placeholder="Add confidential attendance note..." style="font-size:0.8125rem;flex:1;" required>
                <button type="submit" id="btn-drawer-add-note" class="btn btn-secondary btn-sm" style="font-weight:600;">Post</button>
            </form>

            <div id="drawer-timeline" style="display:flex;flex-direction:column;gap:0.5rem;max-height:220px;overflow-y:auto;padding-right:0.25rem;">
                <!-- Timeline items loaded via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Drawer Footer -->
    <div class="drawer-footer">
        <a id="drawer-full-file-btn" href="#" class="btn btn-primary btn-sm" style="font-weight:600;display:flex;align-items:center;gap:0.4rem;width:100%;justify-content:center;">
            <span>Open Full Case File, Documents & Communications</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
    </div>
</aside>

<?php $this->section('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentDrawerLeadId = null;
    let currentLeadData = null;

    // 1. Recalculate column counters and sums reactively
    function updateColumnBadges() {
        document.querySelectorAll('.board-column').forEach(col => {
            const stageId = col.dataset.stageId;
            const cards = Array.from(col.querySelectorAll('.lead-card')).filter(c => c.style.display !== 'none');
            const countEl = document.getElementById(`count-${stageId}`);
            const valueEl = document.getElementById(`value-${stageId}`);
            
            let sum = 0;
            cards.forEach(c => {
                sum += parseFloat(c.dataset.leadValue || 0);
            });

            if (countEl) countEl.textContent = cards.length;
            if (valueEl) valueEl.textContent = '£' + Math.round(sum).toLocaleString();
        });
    }

    // 2. Initialize SortableJS on each column
    const columns = document.querySelectorAll('.sortable-cards');
    columns.forEach(col => {
        new Sortable(col, {
            group: 'pipeline-stages',
            animation: 200,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            easing: 'cubic-bezier(0.2, 0, 0, 1)',
            onEnd: async function (evt) {
                const card = evt.item;
                const leadId = card.dataset.leadId;
                const targetStageId = evt.to.dataset.stageId;
                const originStageId = evt.from.dataset.stageId;

                if (targetStageId === originStageId) return;

                // Optimistic visual update immediately
                updateColumnBadges();
                card.classList.add('card-just-moved');
                setTimeout(() => card.classList.remove('card-just-moved'), 700);

                try {
                    const resp = await Intakely.post('/api/v1/board/move', {
                        lead_id: leadId,
                        stage_id: targetStageId
                    });

                    if (!resp || !resp.ok) {
                        const errData = await resp.json();
                        Toast.error(errData.error || 'Move blocked by compliance checklist.');
                        card.classList.add('card-shake');
                        setTimeout(() => card.classList.remove('card-shake'), 600);
                        evt.from.insertBefore(card, evt.from.children[evt.oldIndex]);
                        updateColumnBadges();
                        return;
                    }

                    const data = await resp.json();
                    if (!data.success) {
                        Toast.error(data.error || 'Move rejected.');
                        card.classList.add('card-shake');
                        setTimeout(() => card.classList.remove('card-shake'), 600);
                        evt.from.insertBefore(card, evt.from.children[evt.oldIndex]);
                        updateColumnBadges();
                        return;
                    }

                    Toast.success(`Matter advanced to ${data.stage_name}`);
                } catch (err) {
                    console.error('Move failed:', err);
                    Toast.error('Network error during move.');
                    evt.from.insertBefore(card, evt.from.children[evt.oldIndex]);
                    updateColumnBadges();
                }
            }
        });
    });

    // 3. Client-Side Quick Board Filtering (Instant 0ms latency)
    const boardSearch = document.getElementById('board-search');
    const filterPills = document.querySelectorAll('.filter-pill');
    let activeFilter = 'all';

    function applyClientFilters() {
        const query = (boardSearch?.value || '').trim().toLowerCase();
        const allCards = document.querySelectorAll('.lead-card');

        allCards.forEach(card => {
            const text = card.dataset.searchText || '';
            const priority = card.dataset.leadPriority || '';
            const breached = card.dataset.leadBreached === '1';
            const conflict = card.dataset.leadConflict === '1';
            const val = parseFloat(card.dataset.leadValue || 0);

            let matchesPill = true;
            if (activeFilter === 'high') matchesPill = (priority === 'high');
            else if (activeFilter === 'breached') matchesPill = breached;
            else if (activeFilter === 'high_value') matchesPill = (val >= 10000);
            else if (activeFilter === 'conflict') matchesPill = conflict;

            const matchesSearch = !query || text.includes(query);

            if (matchesPill && matchesSearch) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        updateColumnBadges();
    }

    if (boardSearch) {
        boardSearch.addEventListener('input', applyClientFilters);
    }

    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeFilter = pill.dataset.filter;
            applyClientFilters();
        });
    });

    // 4. Slide-Over Matter Drawer Functions
    window.openMatterDrawer = async function(leadId) {
        currentDrawerLeadId = leadId;
        const backdrop = document.getElementById('drawer-backdrop');
        const drawer = document.getElementById('matter-drawer');
        if (!drawer || !backdrop) return;

        backdrop.classList.add('active');
        drawer.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Load Lead data via JSON endpoint
        try {
            const res = await fetch(`/leads/${leadId}?format=json`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!data || !data.success) throw new Error('Could not load matter details.');

            currentLeadData = data.lead;
            renderDrawerData(data);
        } catch (err) {
            console.error('Error fetching matter:', err);
            Toast.error('Could not load matter file.');
        }
    };

    window.closeMatterDrawer = function() {
        const backdrop = document.getElementById('drawer-backdrop');
        const drawer = document.getElementById('matter-drawer');
        if (backdrop) backdrop.classList.remove('active');
        if (drawer) drawer.classList.remove('active');
        document.body.style.overflow = '';
        currentDrawerLeadId = null;
    };

    function renderDrawerData(data) {
        const lead = data.lead;
        const custom = (typeof lead.custom_fields === 'string') ? JSON.parse(lead.custom_fields || '{}') : (lead.custom_fields || {});
        
        document.getElementById('drawer-matter-code').textContent = 'MAT-' + lead.id.substring(0, 8).toUpperCase();
        document.getElementById('drawer-matter-name').textContent = lead.name;
        document.getElementById('drawer-service-badge').textContent = lead.service || 'General Practice';
        document.getElementById('drawer-matter-value').textContent = '£' + (lead.value ? Number(lead.value).toLocaleString() : '0');
        
        const stageSelect = document.getElementById('drawer-stage-select');
        if (stageSelect) stageSelect.value = lead.stage_id;

        // Compliance Buttons
        const btnConflict = document.getElementById('btn-toggle-conflict');
        if (btnConflict) {
            if (custom.conflict_check_passed) {
                btnConflict.className = 'compliance-toggle-btn cleared';
                btnConflict.innerHTML = '⚖️ Conflict Cleared';
            } else {
                btnConflict.className = 'compliance-toggle-btn pending';
                btnConflict.innerHTML = '⚖️ Check Pending';
            }
        }

        const btnAml = document.getElementById('btn-toggle-aml');
        if (btnAml) {
            if (custom.id_aml_verified) {
                btnAml.className = 'compliance-toggle-btn cleared';
                btnAml.innerHTML = '🛡️ AML Cleared';
            } else {
                btnAml.className = 'compliance-toggle-btn pending';
                btnAml.innerHTML = '🛡️ AML Pending';
            }
        }

        // Contact info
        const emailEl = document.getElementById('drawer-client-email');
        if (emailEl) {
            emailEl.textContent = lead.email || 'None registered';
            emailEl.href = lead.email ? `mailto:${lead.email}` : '#';
        }
        const phoneEl = document.getElementById('drawer-client-phone');
        if (phoneEl) {
            phoneEl.textContent = lead.phone || 'None registered';
            phoneEl.href = lead.phone ? `tel:${lead.phone}` : '#';
        }
        document.getElementById('drawer-client-company').textContent = lead.company || 'Private Client';
        document.getElementById('drawer-client-assignee').textContent = lead.assignee_name || 'Chambers Rota (Unassigned)';
        
        // Full File link
        document.getElementById('drawer-full-file-btn').href = `/leads/${lead.id}`;

        // Timeline items
        const timelineEl = document.getElementById('drawer-timeline');
        timelineEl.innerHTML = '';
        if (data.activities && data.activities.length > 0) {
            data.activities.slice(0, 6).forEach(act => {
                const item = document.createElement('div');
                item.className = 'drawer-timeline-item';
                item.innerHTML = `
                    <div class="drawer-timeline-dot"></div>
                    <div style="font-size:0.8125rem;color:var(--text-primary);font-weight:500;">${act.content}</div>
                    <div style="font-size:0.6875rem;color:var(--text-tertiary);margin-top:0.15rem;">
                        <span>${act.user_name || 'System'}</span> • <span>${new Date(act.created_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</span>
                    </div>
                `;
                timelineEl.appendChild(item);
            });
        } else {
            timelineEl.innerHTML = '<div style="font-size:0.75rem;color:var(--text-tertiary);">No activities recorded yet.</div>';
        }
    }

    // Toggle SRA Conflict Check
    window.handleToggleConflict = async function() {
        if (!currentDrawerLeadId) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch(`/leads/${currentDrawerLeadId}/quick-update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ toggle_conflict: 1 })
            });
            const data = await res.json();
            if (data.success) {
                const btn = document.getElementById('btn-toggle-conflict');
                const chip = document.getElementById(`chip-conflict-${currentDrawerLeadId}`);
                const card = document.getElementById(`card-${currentDrawerLeadId}`);

                if (data.conflict_cleared) {
                    btn.className = 'compliance-toggle-btn cleared';
                    btn.innerHTML = '⚖️ Conflict Cleared';
                    if (chip) {
                        chip.className = 'compliance-chip chip-passed';
                        chip.textContent = '⚖️ Conflict Cleared';
                    }
                    if (card) card.dataset.leadConflict = '1';
                    Toast.success('Conflict check marked as CLEARED.');
                } else {
                    btn.className = 'compliance-toggle-btn pending';
                    btn.innerHTML = '⚖️ Check Pending';
                    if (chip) {
                        chip.className = 'compliance-chip chip-pending';
                        chip.textContent = '⚖️ Conflict Pending';
                    }
                    if (card) card.dataset.leadConflict = '0';
                    Toast.warning('Conflict check set to PENDING.');
                }
            }
        } catch (err) {
            Toast.error('Failed to update conflict status.');
        }
    };

    // Toggle AML Status
    window.handleToggleAml = async function() {
        if (!currentDrawerLeadId) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch(`/leads/${currentDrawerLeadId}/quick-update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ toggle_aml: 1 })
            });
            const data = await res.json();
            if (data.success) {
                const btn = document.getElementById('btn-toggle-aml');
                if (data.aml_verified) {
                    btn.className = 'compliance-toggle-btn cleared';
                    btn.innerHTML = '🛡️ AML Cleared';
                    Toast.success('AML verification registered.');
                } else {
                    btn.className = 'compliance-toggle-btn pending';
                    btn.innerHTML = '🛡️ AML Pending';
                    Toast.warning('AML verification marked as pending.');
                }
            }
        } catch (err) {
            Toast.error('Failed to update AML status.');
        }
    };

    // Drawer Stage Change
    window.handleDrawerStageChange = async function(stageId) {
        if (!currentDrawerLeadId) return;
        try {
            const resp = await Intakely.post('/api/v1/board/move', {
                lead_id: currentDrawerLeadId,
                stage_id: stageId
            });
            const data = await resp.json();
            if (!data || !data.success) {
                Toast.error(data.error || 'Cannot advance stage.');
                return;
            }

            // Move the card to target column in the DOM without full page refresh!
            const card = document.getElementById(`card-${currentDrawerLeadId}`);
            const targetCol = document.getElementById(`cards-${stageId}`);
            if (card && targetCol) {
                targetCol.prepend(card);
                card.classList.add('card-just-moved');
                setTimeout(() => card.classList.remove('card-just-moved'), 700);
                updateColumnBadges();
            }

            Toast.success(`Advanced matter to ${data.stage_name}`);
        } catch (err) {
            Toast.error('Failed to update stage.');
        }
    };

    // Drawer Quick Note Submit
    window.handleDrawerNoteSubmit = async function(e) {
        e.preventDefault();
        if (!currentDrawerLeadId) return;
        const input = document.getElementById('drawer-note-input');
        const content = (input.value || '').trim();
        if (!content) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const btn = document.getElementById('btn-drawer-add-note');
        btn.disabled = true;

        try {
            const res = await fetch(`/leads/${currentDrawerLeadId}/note`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ content: content })
            });
            const data = await res.json();
            if (data.success) {
                input.value = '';
                const timelineEl = document.getElementById('drawer-timeline');
                const newItem = document.createElement('div');
                newItem.className = 'drawer-timeline-item';
                newItem.innerHTML = `
                    <div class="drawer-timeline-dot"></div>
                    <div style="font-size:0.8125rem;color:var(--text-primary);font-weight:500;">${content}</div>
                    <div style="font-size:0.6875rem;color:var(--text-tertiary);margin-top:0.15rem;">
                        <span>${data.activity?.user_name || 'Counsel'}</span> • <span>just now</span>
                    </div>
                `;
                timelineEl.prepend(newItem);
                Toast.success('Attendance note added.');
            } else {
                Toast.error(data.error || 'Failed to add note.');
            }
        } catch (err) {
            Toast.error('Failed to post note.');
        } finally {
            btn.disabled = false;
        }
    };

    // Keyboard Shortcuts (Esc closes drawer/modal, N opens new matter)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMatterDrawer();
        } else if ((e.key === 'n' || e.key === 'N') && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            openNewMatterModal('<?= e($pipeline['id']) ?>');
        }
    });

    // 5. Listen to Server-Sent Events for live team updates
    document.addEventListener('intakely:lead_moved', (e) => {
        const d = e.detail;
        const card = document.getElementById(`card-${d.lead_id}`);
        const targetContainer = document.getElementById(`cards-${d.stage_id}`);
        if (card && targetContainer && card.parentElement !== targetContainer) {
            targetContainer.prepend(card);
            card.classList.add('card-just-moved');
            setTimeout(() => card.classList.remove('card-just-moved'), 700);
            updateColumnBadges();
            Toast.info(`Matter moved to ${d.stage_name} by colleague.`);
        }
    });

    document.addEventListener('intakely:lead_created', (e) => {
        Toast.success(`New matter captured: ${e.detail.name}`);
    });
});
</script>
<?php $this->endSection(); ?>
