<?php
$this->layout('layouts/app');
$tags = is_string($lead['tags']) ? json_decode($lead['tags'], true) : ($lead['tags'] ?? []);
$customFields = is_string($lead['custom_fields'] ?? null) ? json_decode($lead['custom_fields'], true) : ($lead['custom_fields'] ?? []);
$isBreached = !empty($lead['sla_breached']);
?>

<div style="margin-bottom:1.5rem;">
    <a href="/leads" style="font-size:0.8125rem;color:var(--text-tertiary);">&larr; Back to Leads Table</a>
</div>

<!-- Duplicate Detection Alert Banner -->
<?php if (!empty($duplicates)): ?>
    <div style="background:var(--bg-warning);border:1px solid var(--border-warning);border-radius:var(--radius-xl);padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;gap:0.75rem;align-items:center;">
            <span style="font-size:1.25rem;">⚠️</span>
            <div>
                <strong style="color:var(--text-warning);font-size:0.875rem;">Potential Duplicate Enquiries Detected!</strong>
                <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:0.25rem;">
                    Matched with <?= count($duplicates) ?> other lead(s) via name/email/phone similarity.
                </div>
            </div>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <?php foreach ($duplicates as $dup): ?>
                <form action="/leads/<?= e($lead['id']) ?>/merge" method="POST" onsubmit="return confirm('Merge duplicate record \'<?= e($dup['name']) ?>\' into this lead? Activities and notes will be consolidated.');" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="duplicate_id" value="<?= e($dup['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-secondary" style="border-color:var(--border-warning);">
                        Merge "<?= e($dup['name']) ?>" &rarr;
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Lead Header Banner -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.5rem 2rem;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.5rem;">
            <div>
                <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                    <h1 class="text-title-1"><?= e($lead['name']) ?></h1>
                    <span class="badge badge-<?= ($lead['status'] === 'won') ? 'success' : (($lead['status'] === 'lost') ? 'danger' : 'primary') ?>" style="text-transform:capitalize;">
                        <?= e($lead['status']) ?>
                    </span>
                    <span class="badge badge-neutral" style="font-size:0.75rem;">
                        ⚡ Score: <?= $lead['score'] ?>/100
                    </span>
                    <span class="lead-card-priority <?= e($lead['priority']) ?>" title="Priority: <?= ucfirst(e($lead['priority'])) ?>"></span>
                </div>
                <div style="display:flex;gap:1.5rem;margin-top:0.5rem;color:var(--text-secondary);font-size:0.875rem;flex-wrap:wrap;">
                    <?php if (!empty($lead['service'])): ?>
                        <span><strong>Service:</strong> <?= e($lead['service']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($lead['company'])): ?>
                        <span><strong>Company:</strong> <?= e($lead['company']) ?></span>
                    <?php endif; ?>
                    <span><strong>Source:</strong> <?= e(ucfirst(str_replace('_', ' ', $lead['source'] ?? 'web'))) ?></span>
                    <span><strong>Received:</strong> <?= date('d M Y, H:i', strtotime($lead['created_at'])) ?></span>
                </div>
            </div>

            <!-- Stage & SLA Quick Controls -->
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:0.8125rem;color:var(--text-tertiary);font-weight:500;">Stage:</span>
                    <form action="/leads/<?= e($lead['id']) ?>/stage" method="POST" style="margin:0;">
                        <?= csrf_field() ?>
                        <select name="stage_id" class="form-select" style="font-weight:600;width:auto;padding:0.35rem 0.85rem;" onchange="this.form.submit()">
                            <?php foreach ($stages as $stg): ?>
                                <option value="<?= e($stg['id']) ?>" <?= ($stg['id'] === $lead['stage_id']) ? 'selected' : '' ?>>
                                    <?= e($stg['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <?php if ($isBreached): ?>
                        <span class="sla-timer breached">⚠️ SLA Breached (Response Overdue)</span>
                    <?php elseif (!empty($lead['sla_deadline']) && $lead['status'] === 'open'): ?>
                        <span class="sla-timer ok" data-sla-deadline="<?= e($lead['sla_deadline']) ?>">
                            SLA Active
                        </span>
                    <?php endif; ?>
                    <strong style="font-size:1.25rem;font-family:var(--font-heading);color:var(--text-primary);">
                        <?= !empty($lead['value']) ? '£' . number_format((float)$lead['value']) : '£0' ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Two-Column Workspace Layout -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start;">
    <!-- LEFT: Communication & Unified Timeline -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <!-- Outbound Action Box (Tabs: Email, SMS, WhatsApp, Note, Task) -->
        <div class="card">
            <div class="card-header" style="padding:0.5rem 1rem 0;">
                <div class="tabs" style="border-bottom:none;">
                    <button class="tab active" data-tab="tab-email">Email</button>
                    <button class="tab" data-tab="tab-sms">SMS</button>
                    <button class="tab" data-tab="tab-whatsapp">WhatsApp</button>
                    <button class="tab" data-tab="tab-note">Internal Note</button>
                    <button class="tab" data-tab="tab-task">Assign Task</button>
                </div>
            </div>
            <div class="card-body">
                <!-- Tab: Email -->
                <div class="tab-panel" id="tab-email">
                    <form action="/leads/<?= e($lead['id']) ?>/reply" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="channel" value="email">
                        <div class="form-group" style="margin-bottom:0.75rem;">
                            <label class="form-label" style="font-size:0.75rem;">To:</label>
                            <input type="email" class="form-input" value="<?= e($lead['email']) ?>" readonly style="background:var(--bg-secondary);">
                        </div>
                        <div class="form-group" style="margin-bottom:0.75rem;">
                            <input type="text" name="subject" class="form-input" id="email-subject" placeholder="Subject line..." value="Regarding your enquiry with <?= e($_SESSION['_tenant']['name'] ?? 'our team') ?>">
                        </div>
                        <div class="form-group">
                            <textarea name="message" id="email-body" class="form-textarea" rows="4" placeholder="Compose your email reply here..." required></textarea>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:0.75rem;color:var(--text-tertiary);">Honors tenant quiet hours & STOP opt-outs</span>
                            <button type="submit" class="btn btn-primary btn-sm">Send Email Reply</button>
                        </div>
                    </form>
                </div>

                <!-- Tab: SMS -->
                <div class="tab-panel hidden" id="tab-sms">
                    <form action="/leads/<?= e($lead['id']) ?>/reply" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="channel" value="sms">
                        <div class="form-group" style="margin-bottom:0.75rem;">
                            <label class="form-label" style="font-size:0.75rem;">Mobile Phone:</label>
                            <input type="text" class="form-input" value="<?= e($lead['phone']) ?>" readonly style="background:var(--bg-secondary);">
                        </div>
                        <div class="form-group">
                            <textarea name="message" class="form-textarea" rows="3" placeholder="Compose SMS (160 characters per segment)..." required></textarea>
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn btn-primary btn-sm">Send SMS</button>
                        </div>
                    </form>
                </div>

                <!-- Tab: WhatsApp -->
                <div class="tab-panel hidden" id="tab-whatsapp">
                    <form action="/leads/<?= e($lead['id']) ?>/reply" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="channel" value="whatsapp">
                        <div class="form-group" style="margin-bottom:0.75rem;">
                            <label class="form-label" style="font-size:0.75rem;">WhatsApp Recipient:</label>
                            <input type="text" class="form-input" value="<?= e($lead['phone']) ?>" readonly style="background:var(--bg-secondary);">
                        </div>
                        <div class="form-group">
                            <textarea name="message" class="form-textarea" rows="3" placeholder="Compose WhatsApp message..." required></textarea>
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn btn-primary btn-sm">Send WhatsApp Message</button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Note -->
                <div class="tab-panel hidden" id="tab-note">
                    <form action="/leads/<?= e($lead['id']) ?>/note" method="POST">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <textarea name="content" class="form-textarea" rows="3" placeholder="Add private internal note or call summary (@mentions supported)..." required></textarea>
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn btn-secondary btn-sm">Post Internal Note</button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Task -->
                <div class="tab-panel hidden" id="tab-task">
                    <form action="/leads/<?= e($lead['id']) ?>/task" method="POST">
                        <?= csrf_field() ?>
                        <div style="display:grid;grid-template-columns:2fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                            <input type="text" name="title" class="form-input" placeholder="Task description..." required>
                            <input type="datetime-local" name="due_at" class="form-input">
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn btn-secondary btn-sm">Create Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Unified Activity Timeline -->
        <div class="card">
            <div class="card-header">
                <h2 class="text-title-3">Unified Activity Timeline</h2>
                <span style="font-size:0.75rem;color:var(--text-tertiary);"><?= count($activities) ?> events logged</span>
            </div>
            <div class="card-body">
                <?php if (empty($activities)): ?>
                    <div style="text-align:center;padding:2rem;color:var(--text-tertiary);">
                        No timeline events recorded yet.
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($activities as $act): ?>
                            <?php
                            $actType = $act['type'];
                            $dotClass = match ($actType) {
                                'email_sent', 'email_received' => 'email',
                                'note' => 'note',
                                'stage_change' => 'stage',
                                'task_created', 'task_completed' => 'task',
                                default => 'system',
                            };
                            ?>
                            <div class="timeline-item">
                                <span class="timeline-dot <?= $dotClass ?>"></span>
                                <div class="timeline-time">
                                    <strong><?= ucfirst(str_replace('_', ' ', $actType)) ?></strong> •
                                    <?= date('d M Y, H:i', strtotime($act['created_at'])) ?>
                                    <?php if (!empty($act['user_name'])): ?>
                                        by <?= e($act['user_name']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-content" style="white-space:pre-wrap;background:var(--bg-secondary);padding:0.75rem 1rem;border-radius:var(--radius-lg);margin-top:0.35rem;border:1px solid var(--border-default);">
                                    <?= e($act['content']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: AI Assistant, Compliance Checklist, & Contact Meta -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <!-- AI Assistant Widget -->
        <div class="card" style="border-color:var(--accent-300);">
            <div class="card-header" style="background:var(--bg-accent);">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:1.125rem;">✨</span>
                    <h3 class="text-title-3" style="font-size:1rem;color:var(--accent-900);">AI Assistant</h3>
                </div>
                <span class="badge badge-primary">Guardrails Active</span>
            </div>
            <div class="card-body" style="font-size:0.875rem;">
                <p style="color:var(--text-secondary);margin-bottom:1rem;font-size:0.8125rem;">
                    Extract insights, synthesize matter details, and draft client communications.
                </p>

                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-ai-summary" onclick="triggerAiSummary()">
                        📋 Summarize Matter
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-ai-action" onclick="triggerAiNextAction()">
                        💡 Suggest Next Action
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-ai-reply" onclick="triggerAiDraftReply()">
                        ✍️ Draft Warm Reply
                    </button>
                </div>

                <!-- AI Output Display Box -->
                <div id="ai-output-box" class="hidden" style="margin-top:1rem;padding:0.75rem;background:var(--bg-secondary);border-radius:var(--radius-lg);border-left:3px solid var(--accent-500);font-size:0.8125rem;line-height:1.5;">
                    <div id="ai-output-content"></div>
                </div>
            </div>
        </div>

        <!-- Compliance & Regulatory Checklist -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-title-3" style="font-size:0.9375rem;">Compliance Checklist</h3>
                <span class="badge badge-neutral">Stage Gates</span>
            </div>
            <div class="card-body" style="font-size:0.8125rem;">
                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <label class="form-checkbox">
                        <input type="checkbox" <?= !empty($customFields['conflict_check_passed']) ? 'checked' : '' ?> disabled>
                        <span>Conflict of Interest Check Cleared</span>
                    </label>
                    <label class="form-checkbox">
                        <input type="checkbox" <?= !empty($customFields['id_aml_verified']) ? 'checked' : '' ?> disabled>
                        <span>ID / AML Verification Documented</span>
                    </label>
                    <label class="form-checkbox">
                        <input type="checkbox" <?= !empty($customFields['client_care_letter_sent']) ? 'checked' : '' ?> disabled>
                        <span>Client Care Letter & Terms Sent</span>
                    </label>
                    <label class="form-checkbox">
                        <input type="checkbox" <?= !empty($customFields['retainer_received']) ? 'checked' : '' ?> disabled>
                        <span>Retainer / Deposit Payment Received</span>
                    </label>
                </div>
                <div style="font-size:0.6875rem;color:var(--text-tertiary);margin-top:1rem;">
                    Compliance checks are enforced before advancing to "Instructed" status.
                </div>
            </div>
        </div>

        <!-- Contact & Lead Metadata -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-title-3" style="font-size:0.9375rem;">Contact Information</h3>
            </div>
            <div class="card-body" style="font-size:0.8125rem;display:flex;flex-direction:column;gap:0.75rem;">
                <div>
                    <span style="color:var(--text-tertiary);">Email:</span>
                    <div><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email'] ?: 'None provided') ?></a></div>
                </div>
                <div>
                    <span style="color:var(--text-tertiary);">Phone:</span>
                    <div><a href="tel:<?= e($lead['phone']) ?>"><?= e($lead['phone'] ?: 'None provided') ?></a></div>
                </div>
                <div>
                    <span style="color:var(--text-tertiary);">Assignee:</span>
                    <div style="font-weight:600;"><?= e($lead['assignee_name'] ?: 'Unassigned') ?></div>
                </div>
                <?php if (!empty($lead['utm_source']) || !empty($lead['utm_campaign'])): ?>
                    <div style="border-top:1px solid var(--border-default);padding-top:0.5rem;">
                        <span style="color:var(--text-tertiary);">Attribution:</span>
                        <div style="font-size:0.75rem;color:var(--text-secondary);">
                            <?= e($lead['utm_source'] ?? '') ?> / <?= e($lead['utm_medium'] ?? '') ?> (<?= e($lead['utm_campaign'] ?? '') ?>)
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script>
async function triggerAiSummary() {
    const box = document.getElementById('ai-output-box');
    const content = document.getElementById('ai-output-content');
    box.classList.remove('hidden');
    content.innerHTML = '<em>Generating AI matter summary...</em>';

    const resp = await Intakely.post('/api/v1/ai/summarize', { lead_id: '<?= e($lead['id']) ?>' });
    if (resp && resp.ok) {
        const data = await resp.json();
        content.innerHTML = `<strong>Matter Summary:</strong><p style="margin-top:0.25rem;">${data.summary}</p>`;
    } else {
        content.innerHTML = '<em>Unable to load summary.</em>';
    }
}

async function triggerAiNextAction() {
    const box = document.getElementById('ai-output-box');
    const content = document.getElementById('ai-output-content');
    box.classList.remove('hidden');
    content.innerHTML = '<em>Evaluating pipeline state & recommending action...</em>';

    const resp = await Intakely.post('/api/v1/ai/suggest-action', { lead_id: '<?= e($lead['id']) ?>' });
    if (resp && resp.ok) {
        const data = await resp.json();
        content.innerHTML = `<strong>Recommended Next Step:</strong><p style="margin-top:0.25rem;">${data.suggestion}</p>`;
    } else {
        content.innerHTML = '<em>Unable to load recommendation.</em>';
    }
}

async function triggerAiDraftReply() {
    const box = document.getElementById('ai-output-box');
    const content = document.getElementById('ai-output-content');
    box.classList.remove('hidden');
    content.innerHTML = '<em>Drafting tone-tailored reply...</em>';

    const resp = await Intakely.post('/api/v1/ai/draft-reply', { lead_id: '<?= e($lead['id']) ?>' });
    if (resp && resp.ok) {
        const data = await resp.json();
        content.innerHTML = `<strong>Draft Generated!</strong> Auto-filled into Email tab above.`;
        document.getElementById('email-subject').value = data.subject || '';
        document.getElementById('email-body').value = data.body || '';
        // Switch to email tab
        document.querySelector('[data-tab="tab-email"]').click();
    } else {
        content.innerHTML = '<em>Unable to draft reply.</em>';
    }
}
</script>
<?php $this->endSection(); ?>
