<?php $this->layout('layouts/app'); ?>

<div class="dashboard-header-row" style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 class="text-title-1" style="font-weight:700;color:var(--text-primary);letter-spacing:-0.02em;">Executive Practice Overview</h1>
        <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.35rem;">
            Real-time matter velocity, instruction conversion, court hearings, and regulatory compliance audit health.
        </p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
        <span class="badge" style="background:var(--bg-secondary);border:1px solid var(--border-default);color:var(--text-secondary);font-size:0.75rem;padding:0.4rem 0.85rem;border-radius:var(--radius-full);">
            Chamber Year: 2026/27 Q4
        </span>
        <a href="/pipeline" class="btn btn-secondary btn-sm" style="font-weight:600;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="11" rx="1"/></svg>
            <span>Open Case Board</span>
        </a>
    </div>
</div>

<!-- 4 High-Impact KPI Stat Cards (Matching Behance Law Firm Layout) -->
<div class="stat-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1.25rem;margin-bottom:2rem;">
    <!-- KPI 1: Active Cases -->
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem;">
            <div class="stat-card-label" style="font-size:0.75rem;font-weight:700;color:var(--text-tertiary);letter-spacing:0.05em;text-transform:uppercase;">Total Active Matters</div>
            <div class="stat-icon-wrap" style="background:rgba(59, 130, 246, 0.12);color:#3B82F6;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
        </div>
        <div class="stat-card-value" style="font-size:2.25rem;font-weight:700;color:var(--text-primary);line-height:1;"><?= number_format($kpis['total_open']) ?></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;font-size:0.75rem;">
            <span class="stat-card-trend up">↑ +12.4% vs last month</span>
            <span style="color:var(--text-tertiary);">74% Chamber Capacity</span>
        </div>
        <div style="height:4px;background:var(--bg-secondary);border-radius:var(--radius-full);margin-top:0.6rem;overflow:hidden;">
            <div style="height:100%;width:74%;background:linear-gradient(90deg, #3B82F6, #60A5FA);border-radius:var(--radius-full);"></div>
        </div>
    </div>

    <!-- KPI 2: Pending Consultations -->
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem;">
            <div class="stat-card-label" style="font-size:0.75rem;font-weight:700;color:var(--text-tertiary);letter-spacing:0.05em;text-transform:uppercase;">Pending Consultations</div>
            <div class="stat-icon-wrap" style="background:rgba(245, 158, 11, 0.12);color:#F59E0B;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
        </div>
        <div class="stat-card-value" style="font-size:2.25rem;font-weight:700;color:var(--text-primary);line-height:1;">18</div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;font-size:0.75rem;">
            <span class="stat-card-trend" style="background:rgba(245, 158, 11, 0.15);color:#FBBF24;">4 Hearings this week</span>
            <span style="color:var(--text-tertiary);">Avg response: 18m</span>
        </div>
        <div style="height:4px;background:var(--bg-secondary);border-radius:var(--radius-full);margin-top:0.6rem;overflow:hidden;">
            <div style="height:100%;width:82%;background:linear-gradient(90deg, #F59E0B, #FCD34D);border-radius:var(--radius-full);"></div>
        </div>
    </div>

    <!-- KPI 3: Instruction Conversion -->
    <div class="stat-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem;">
            <div class="stat-card-label" style="font-size:0.75rem;font-weight:700;color:var(--text-tertiary);letter-spacing:0.05em;text-transform:uppercase;">Instruction Rate</div>
            <div class="stat-icon-wrap" style="background:rgba(16, 185, 129, 0.12);color:#10B981;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
        </div>
        <div class="stat-card-value" style="font-size:2.25rem;font-weight:700;color:var(--text-primary);line-height:1;"><?= $kpis['conversion_rate'] ?>%</div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;font-size:0.75rem;">
            <span class="stat-card-trend up">↑ +4.1% instruction lift</span>
            <span style="color:var(--text-tertiary);"><?= $kpis['won_count'] ?> won matters</span>
        </div>
        <div style="height:4px;background:var(--bg-secondary);border-radius:var(--radius-full);margin-top:0.6rem;overflow:hidden;">
            <div style="height:100%;width:<?= min(100, (float)$kpis['conversion_rate']) ?>%;background:linear-gradient(90deg, #10B981, #34D399);border-radius:var(--radius-full);"></div>
        </div>
    </div>

    <!-- KPI 4: Pipeline Fee Retainers -->
    <div class="stat-card" style="<?= ($kpis['sla_breaches'] > 0) ? 'border-color:rgba(239, 68, 68, 0.3);' : '' ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem;">
            <div class="stat-card-label" style="font-size:0.75rem;font-weight:700;color:var(--text-tertiary);letter-spacing:0.05em;text-transform:uppercase;">Pipeline Fee Valuation</div>
            <div class="stat-icon-wrap" style="background:rgba(99, 102, 241, 0.12);color:#818CF8;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
        </div>
        <div class="stat-card-value" style="font-size:2.25rem;font-weight:700;color:var(--text-primary);line-height:1;">£<?= number_format($kpis['pipeline_value']) ?></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;font-size:0.75rem;">
            <span class="stat-card-trend" style="background:rgba(99, 102, 241, 0.15);color:#A5B4FC;">Weighted forecast</span>
            <span style="<?= ($kpis['sla_breaches'] > 0) ? 'color:#F87171;font-weight:600;' : 'color:var(--text-tertiary);' ?>">
                <?= ($kpis['sla_breaches'] > 0) ? "⚠️ {$kpis['sla_breaches']} SLA alerts" : '100% SLA compliant' ?>
            </span>
        </div>
        <div style="height:4px;background:var(--bg-secondary);border-radius:var(--radius-full);margin-top:0.6rem;overflow:hidden;">
            <div style="height:100%;width:88%;background:linear-gradient(90deg, #6366F1, #818CF8);border-radius:var(--radius-full);"></div>
        </div>
    </div>
</div>

<!-- Main Workspace Layout: 2 Columns (Behance Law Firm Structure) -->
<div style="display:grid;grid-template-columns:1.8fr 1.2fr;gap:1.5rem;align-items:start;">
    <!-- LEFT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <!-- Pipeline Stage Distribution & Funnel -->
        <div class="card" style="box-shadow:var(--shadow-md);">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2 class="text-title-3" style="font-weight:700;">Case Pipeline Volume & Stage Velocity</h2>
                    <p style="font-size:0.75rem;color:var(--text-tertiary);margin-top:2px;">Active matter breakdown across Chambers intake stages</p>
                </div>
                <a href="/pipeline" class="btn btn-ghost btn-sm" style="font-size:0.8125rem;">Open Pipeline Board &rarr;</a>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:1.15rem;">
                    <?php foreach ($stagesBreakdown as $sb): ?>
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8125rem;margin-bottom:0.35rem;">
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <span style="width:10px;height:10px;border-radius:50%;background:<?= e($sb['color']) ?>;box-shadow:0 0 8px <?= e($sb['color']) ?>;display:inline-block;"></span>
                                    <span style="font-weight:600;color:var(--text-primary);"><?= e($sb['name']) ?></span>
                                </div>
                                <div style="font-size:0.8125rem;">
                                    <span style="color:var(--text-tertiary);"><?= $sb['count'] ?> matters</span>
                                    <span style="color:var(--text-tertiary);margin:0 0.35rem;">•</span>
                                    <strong style="color:var(--text-primary);font-variant-numeric:tabular-nums;">£<?= number_format((float)$sb['total_val']) ?></strong>
                                </div>
                            </div>
                            <div style="height:8px;background:var(--bg-secondary);border-radius:var(--radius-full);overflow:hidden;">
                                <div style="height:100%;width:<?= min(100, max(6, $sb['count'] * 7)) ?>%;background:<?= e($sb['color']) ?>;border-radius:var(--radius-full);transition:width 0.5s ease;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Court Hearings & Appointments Widget (Behance Hearing Docket) -->
        <div class="card" style="box-shadow:var(--shadow-md);">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2 class="text-title-3" style="font-weight:700;">Upcoming Hearings & Consultations</h2>
                    <p style="font-size:0.75rem;color:var(--text-tertiary);margin-top:2px;">Scheduled hearings, client conferences, and tribunal dates</p>
                </div>
                <a href="/appointments" class="btn btn-ghost btn-sm" style="font-size:0.8125rem;">Full Calendar &rarr;</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="display:flex;flex-direction:column;">
                    <!-- Static / Seeded Hearings List -->
                    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-default);display:flex;align-items:center;gap:1rem;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <div style="background:rgba(59, 130, 246, 0.12);border:1px solid rgba(59, 130, 246, 0.25);border-radius:10px;padding:0.4rem 0.65rem;text-align:center;min-width:52px;">
                                <div style="font-size:0.6875rem;font-weight:700;color:#60A5FA;text-transform:uppercase;">OCT</div>
                                <div style="font-size:1.125rem;font-weight:800;color:#FFFFFF;line-height:1;">24</div>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:0.875rem;color:var(--text-primary);">Kensington Property Acquisition — Completion Hearing</div>
                                <div style="font-size:0.75rem;color:var(--text-tertiary);margin-top:2px;">Counsel: David Ross • Location: High Court Chancery Division</div>
                            </div>
                        </div>
                        <span class="badge" style="background:rgba(14, 165, 233, 0.14);color:#38BDF8;border:1px solid rgba(14, 165, 233, 0.3);font-size:0.75rem;padding:0.25rem 0.65rem;border-radius:var(--radius-full);">Conveyancing</span>
                    </div>

                    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-default);display:flex;align-items:center;gap:1rem;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <div style="background:rgba(245, 158, 11, 0.12);border:1px solid rgba(245, 158, 11, 0.25);border-radius:10px;padding:0.4rem 0.65rem;text-align:center;min-width:52px;">
                                <div style="font-size:0.6875rem;font-weight:700;color:#FBBF24;text-transform:uppercase;">OCT</div>
                                <div style="font-size:1.125rem;font-weight:800;color:#FFFFFF;line-height:1;">26</div>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:0.875rem;color:var(--text-primary);">Sterling vs. Horizon Ltd — Case Management Conference</div>
                                <div style="font-size:0.75rem;color:var(--text-tertiary);margin-top:2px;">Counsel: Eleanor Vance • Location: Royal Courts of Justice</div>
                            </div>
                        </div>
                        <span class="badge" style="background:rgba(99, 102, 241, 0.14);color:#818CF8;border:1px solid rgba(99, 102, 241, 0.3);font-size:0.75rem;padding:0.25rem 0.65rem;border-radius:var(--radius-full);">Litigation</span>
                    </div>

                    <div style="padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <div style="background:rgba(168, 85, 247, 0.12);border:1px solid rgba(168, 85, 247, 0.25);border-radius:10px;padding:0.4rem 0.65rem;text-align:center;min-width:52px;">
                                <div style="font-size:0.6875rem;font-weight:700;color:#C084FC;text-transform:uppercase;">OCT</div>
                                <div style="font-size:1.125rem;font-weight:800;color:#FFFFFF;line-height:1;">29</div>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:0.875rem;color:var(--text-primary);">Cavendish Estate Trust Execution & Probate</div>
                                <div style="font-size:0.75rem;color:var(--text-tertiary);margin-top:2px;">Counsel: Sarah Jenkins • Location: Chambers Boardroom 1</div>
                            </div>
                        </div>
                        <span class="badge" style="background:rgba(168, 85, 247, 0.14);color:#C084FC;border:1px solid rgba(168, 85, 247, 0.3);font-size:0.75rem;padding:0.25rem 0.65rem;border-radius:var(--radius-full);">Probate</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <!-- SRA & Legal Regulatory Compliance Health (Law Firm Differentiator) -->
        <div class="card" style="box-shadow:var(--shadow-md);">
            <div class="card-header">
                <h2 class="text-title-3" style="font-weight:700;">Regulatory Compliance Audit</h2>
                <p style="font-size:0.75rem;color:var(--text-tertiary);margin-top:2px;">SRA & AML regulatory checks on open matters</p>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:1rem;">
                    <!-- Check 1: Conflict Checks -->
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8125rem;margin-bottom:0.35rem;">
                            <span style="font-weight:600;color:var(--text-primary);">Conflict Checks Cleared</span>
                            <span style="font-weight:700;color:#34D399;">100%</span>
                        </div>
                        <div style="height:6px;background:var(--bg-secondary);border-radius:var(--radius-full);overflow:hidden;">
                            <div style="height:100%;width:100%;background:#10B981;border-radius:var(--radius-full);"></div>
                        </div>
                        <div style="font-size:0.6875rem;color:var(--text-tertiary);margin-top:3px;">No partner conflicts identified across active register</div>
                    </div>

                    <!-- Check 2: AML & Identity -->
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8125rem;margin-bottom:0.35rem;">
                            <span style="font-weight:600;color:var(--text-primary);">ID & AML Checks Verified</span>
                            <span style="font-weight:700;color:#60A5FA;">96%</span>
                        </div>
                        <div style="height:6px;background:var(--bg-secondary);border-radius:var(--radius-full);overflow:hidden;">
                            <div style="height:100%;width:96%;background:#3B82F6;border-radius:var(--radius-full);"></div>
                        </div>
                        <div style="font-size:0.6875rem;color:var(--text-tertiary);margin-top:3px;">Electronic passport & biometric verification verified</div>
                    </div>

                    <!-- Check 3: Client Care Letters -->
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8125rem;margin-bottom:0.35rem;">
                            <span style="font-weight:600;color:var(--text-primary);">Client Care Letters Executed</span>
                            <span style="font-weight:700;color:#C084FC;">91%</span>
                        </div>
                        <div style="height:6px;background:var(--bg-secondary);border-radius:var(--radius-full);overflow:hidden;">
                            <div style="height:100%;width:91%;background:#A855F7;border-radius:var(--radius-full);"></div>
                        </div>
                        <div style="font-size:0.6875rem;color:var(--text-tertiary);margin-top:3px;">Signed terms of business and cost estimates on file</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Counsel Workload & Practice Leaderboard -->
        <div class="card" style="box-shadow:var(--shadow-md);">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2 class="text-title-3" style="font-weight:700;">Counsel Caseload</h2>
                    <p style="font-size:0.75rem;color:var(--text-tertiary);margin-top:2px;">Active matters assigned per partner & associate</p>
                </div>
                <a href="/settings" class="btn btn-ghost btn-sm" style="font-size:0.8125rem;">Team &rarr;</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="display:flex;flex-direction:column;">
                    <?php if (!empty($attorneyLeaderboard)): ?>
                        <?php foreach ($attorneyLeaderboard as $counsel): ?>
                            <div style="padding:0.85rem 1.25rem;border-bottom:1px solid var(--border-default);display:flex;align-items:center;justify-content:space-between;">
                                <div style="display:flex;align-items:center;gap:0.75rem;">
                                    <div class="avatar avatar-sm" style="background:var(--accent-600);color:white;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                        <?= strtoupper(substr($counsel['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:0.8125rem;color:var(--text-primary);"><?= e($counsel['name']) ?></div>
                                        <div style="font-size:0.6875rem;color:var(--text-tertiary);text-transform:capitalize;"><?= e($counsel['role']) ?></div>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-weight:700;font-size:0.8125rem;color:var(--text-primary);"><?= $counsel['active_matters'] ?> matters</div>
                                    <div style="font-size:0.6875rem;color:#34D399;font-weight:600;">£<?= number_format((float)$counsel['total_val']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback / Seeded Attorneys -->
                        <div style="padding:0.85rem 1.25rem;border-bottom:1px solid var(--border-default);display:flex;align-items:center;justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <div class="avatar avatar-sm" style="background:#2563EB;color:white;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;">E</div>
                                <div>
                                    <div style="font-weight:600;font-size:0.8125rem;color:var(--text-primary);">Eleanor Vance</div>
                                    <div style="font-size:0.6875rem;color:var(--text-tertiary);">Managing Partner</div>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700;font-size:0.8125rem;color:var(--text-primary);">14 matters</div>
                                <div style="font-size:0.6875rem;color:#34D399;font-weight:600;">£68,500</div>
                            </div>
                        </div>

                        <div style="padding:0.85rem 1.25rem;border-bottom:1px solid var(--border-default);display:flex;align-items:center;justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <div class="avatar avatar-sm" style="background:#4F46E5;color:white;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;">D</div>
                                <div>
                                    <div style="font-weight:600;font-size:0.8125rem;color:var(--text-primary);">David Ross</div>
                                    <div style="font-size:0.6875rem;color:var(--text-tertiary);">Associate Counsel</div>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700;font-size:0.8125rem;color:var(--text-primary);">11 matters</div>
                                <div style="font-size:0.6875rem;color:#34D399;font-weight:600;">£44,000</div>
                            </div>
                        </div>

                        <div style="padding:0.85rem 1.25rem;display:flex;align-items:center;justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <div class="avatar avatar-sm" style="background:#7C3AED;color:white;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;">S</div>
                                <div>
                                    <div style="font-weight:600;font-size:0.8125rem;color:var(--text-primary);">Sarah Jenkins</div>
                                    <div style="font-size:0.6875rem;color:var(--text-tertiary);">Senior Solicitor</div>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700;font-size:0.8125rem;color:var(--text-primary);">9 matters</div>
                                <div style="font-size:0.6875rem;color:#34D399;font-weight:600;">£38,000</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- High Priority Enquiries Table -->
        <div class="card" style="box-shadow:var(--shadow-md);">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h2 class="text-title-3" style="font-weight:700;">High Priority Inquiries</h2>
                <a href="/leads?priority=high" class="btn btn-ghost btn-sm" style="font-size:0.8125rem;">View All &rarr;</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="data-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Client / Matter</th>
                                <th>Service</th>
                                <th>Value</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLeads as $rl): ?>
                                <tr>
                                    <td>
                                        <a href="/leads/<?= e($rl['id']) ?>" style="font-weight:600;color:var(--text-primary);text-decoration:none;display:block;">
                                            <?= e($rl['name']) ?>
                                        </a>
                                        <span style="font-size:0.6875rem;color:var(--text-tertiary);"><?= e($rl['stage_name'] ?? 'Inquiry') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:rgba(59, 130, 246, 0.12);color:#60A5FA;border:1px solid rgba(59, 130, 246, 0.25);font-size:0.6875rem;padding:0.2rem 0.5rem;border-radius:var(--radius-full);">
                                            <?= e($rl['service'] ?: 'General Legal') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color:#34D399;font-size:0.8125rem;">£<?= number_format((float)$rl['value']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:rgba(245, 158, 11, 0.15);color:#FBBF24;font-size:0.6875rem;font-weight:700;">
                                            ⚡ <?= $rl['score'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
