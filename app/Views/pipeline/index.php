<?php $this->layout('layouts/app'); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
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

<!-- Filters Toolbar & Quick Chips (Behance Law Firm Style) -->
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
                    <div class="lead-card" data-lead-id="<?= e($lead['id']) ?>" data-lead-value="<?= (float)($lead['value'] ?? 0) ?>" data-lead-priority="<?= e($lead['priority']) ?>" data-lead-breached="<?= $isBreached ? '1' : '0' ?>" data-lead-conflict="<?= $conflictClear ? '1' : '0' ?>" data-search-text="<?= strtolower(e($lead['name'] . ' ' . ($lead['company'] ?? '') . ' ' . ($lead['service'] ?? ''))) ?>" id="card-<?= e($lead['id']) ?>">
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
                                <a href="/leads/<?= e($lead['id']) ?>" class="lead-card-name" style="text-decoration:none;display:block;">
                                    <?= e($lead['name']) ?>
                                </a>
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
                            <span class="compliance-chip <?= $conflictClear ? 'chip-passed' : 'chip-pending' ?>">
                                <?= $conflictClear ? '⚖️ Conflict Cleared' : '⚖️ Conflict Pending' ?>
                            </span>
                            <?php if ($amlVerified): ?>
                                <span class="compliance-chip chip-passed">
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

<?php $this->section('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
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
                        // Shake card and move back
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

    // 4. Listen to Server-Sent Events for live team updates
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
