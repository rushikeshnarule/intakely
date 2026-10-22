<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Book Consultation') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        body {
            background: var(--bg-full);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .book-card {
            width: 100%;
            max-width: 640px;
            background: var(--bg-primary);
            border: 1px solid var(--border-default);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            padding: 2.5rem 2rem;
        }
        .slot-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-default);
            border-radius: var(--radius-md);
            background: var(--bg-primary);
            cursor: pointer;
            font-size: 0.8125rem;
            font-weight: 500;
        }
        .slot-btn:hover, .slot-btn.selected {
            background: var(--accent-600);
            color: white;
            border-color: var(--accent-600);
        }
    </style>
</head>
<body>
    <div class="book-card">
        <?php if ($booked): ?>
            <div style="text-align:center;padding:2rem 0;">
                <div style="width:56px;height:56px;background:var(--bg-success);color:var(--text-success);border-radius:var(--radius-full);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h1 class="text-title-2" style="margin-bottom:0.75rem;">Consultation Confirmed</h1>
                <p style="color:var(--text-secondary);font-size:1rem;">
                    Your appointment with <strong><?= e($user['name']) ?></strong> has been scheduled for:
                </p>
                <div style="margin:1.5rem 0;font-size:1.125rem;font-weight:600;color:var(--accent-700);">
                    <?= date('l, d F Y \a\t H:i', strtotime($startAt)) ?>
                </div>
                <p style="font-size:0.875rem;color:var(--text-tertiary);">
                    A calendar invitation has been generated and dispatched to your email address.
                </p>
            </div>
        <?php else: ?>
            <div style="margin-bottom:2rem;">
                <div style="font-size:0.8125rem;font-weight:600;color:var(--accent-600);text-transform:uppercase;">
                    <?= e($user['tenant_name']) ?>
                </div>
                <h1 class="text-title-2" style="margin-top:0.25rem;">Book with <?= e($user['name']) ?></h1>
                <p style="color:var(--text-secondary);font-size:0.875rem;">
                    <?= e($user['role']) ?> • 45-Minute Consultation
                </p>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="date" id="selected-date" value="<?= array_key_first($slots) ?>">
                <input type="hidden" name="time" id="selected-time" value="10:00">

                <div class="form-group">
                    <label class="form-label">1. Choose Date & Time Slot</label>
                    <div style="display:flex;flex-direction:column;gap:1rem;margin-top:0.5rem;">
                        <?php foreach ($slots as $d => $times): ?>
                            <div style="background:var(--bg-secondary);padding:0.75rem 1rem;border-radius:var(--radius-xl);border:1px solid var(--border-default);">
                                <div style="font-weight:600;font-size:0.8125rem;margin-bottom:0.5rem;color:var(--text-primary);">
                                    <?= date('l, d M', strtotime($d)) ?>
                                </div>
                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                    <?php foreach ($times as $t): ?>
                                        <button type="button" class="slot-btn" onclick="selectSlot('<?= $d ?>', '<?= $t ?>', this)">
                                            <?= $t ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="border-top:1px solid var(--border-default);padding-top:1.5rem;margin-top:1.5rem;">
                    <div class="form-group">
                        <label class="form-label" for="name">Your Full Name *</label>
                        <input type="text" class="form-input" id="name" name="name" required placeholder="Jane Smith">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address *</label>
                            <input type="email" class="form-input" id="email" name="email" required placeholder="jane@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Mobile Phone *</label>
                            <input type="tel" class="form-input" id="phone" name="phone" required placeholder="+44 7700 900123">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:1rem;">
                        Confirm & Schedule Consultation &rarr;
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function selectSlot(date, time, btn) {
            document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            document.getElementById('selected-date').value = date;
            document.getElementById('selected-time').value = time;
        }
    </script>
</body>
</html>
