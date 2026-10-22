<?php
$primaryColor = $tenantSettings['brand_colors']['primary'] ?? '#2563eb';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Client Intake Form') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        :root {
            --accent-600: <?= e($primaryColor) ?>;
            --accent-700: <?= e($primaryColor) ?>;
        }
        body {
            background: var(--bg-full);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .form-card {
            width: 100%;
            max-width: 600px;
            background: var(--bg-primary);
            border: 1px solid var(--border-default);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            padding: 2.5rem 2rem;
        }
        .hp-field {
            display: none !important;
            visibility: hidden !important;
        }
    </style>
</head>
<body>
    <div class="form-card">
        <?php if ($submitted): ?>
            <!-- Success Message Display -->
            <div style="text-align:center;padding:2rem 0;">
                <div style="width:56px;height:56px;background:var(--bg-success);color:var(--text-success);border-radius:var(--radius-full);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h1 class="text-title-2" style="margin-bottom:0.75rem;">Enquiry Received</h1>
                <p style="color:var(--text-secondary);font-size:1rem;line-height:1.6;">
                    <?= e($successMessage ?? 'Thank you. We have received your submission.') ?>
                </p>
                <div style="margin-top:2rem;">
                    <a href="/f/<?= e($form['slug']) ?>" class="btn btn-secondary btn-sm">Submit Another Response</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Form Header -->
            <div style="margin-bottom:2rem;text-align:left;">
                <div style="font-size:0.8125rem;font-weight:600;color:var(--accent-600);text-transform:uppercase;letter-spacing:0.04em;">
                    <?= e($form['tenant_name']) ?>
                </div>
                <h1 class="text-title-2" style="margin-top:0.35rem;"><?= e($form['name']) ?></h1>
                <?php if (!empty($form['description'])): ?>
                    <p style="color:var(--text-secondary);font-size:0.875rem;margin-top:0.35rem;"><?= e($form['description']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Form Submission -->
            <form action="/f/<?= e($form['slug']) ?>" method="POST">
                <!-- Honeypot spam trap -->
                <div class="hp-field">
                    <label>Do not fill this field</label>
                    <input type="text" name="_hp_check" autocomplete="off" tabindex="-1">
                </div>

                <!-- Hidden UTM fields -->
                <input type="hidden" name="utm_source" value="<?= e($_GET['utm_source'] ?? '') ?>">
                <input type="hidden" name="utm_medium" value="<?= e($_GET['utm_medium'] ?? '') ?>">
                <input type="hidden" name="utm_campaign" value="<?= e($_GET['utm_campaign'] ?? '') ?>">

                <?php foreach ($fields as $field): ?>
                    <?php
                    $fName = $field['name'];
                    $fLabel = $field['label'] ?? ucfirst($fName);
                    $fType = $field['type'] ?? 'text';
                    $fReq = !empty($field['required']);
                    $fPlaceholder = $field['placeholder'] ?? '';
                    ?>
                    <div class="form-group">
                        <label class="form-label" for="f-<?= e($fName) ?>">
                            <?= e($fLabel) ?> <?= $fReq ? '<span style="color:var(--text-danger);">*</span>' : '' ?>
                        </label>

                        <?php if ($fType === 'textarea'): ?>
                            <textarea class="form-textarea" id="f-<?= e($fName) ?>" name="<?= e($fName) ?>" rows="3" placeholder="<?= e($fPlaceholder) ?>" <?= $fReq ? 'required' : '' ?>></textarea>

                        <?php elseif ($fType === 'select'): ?>
                            <select class="form-select" id="f-<?= e($fName) ?>" name="<?= e($fName) ?>" <?= $fReq ? 'required' : '' ?>>
                                <option value="">Please select an option...</option>
                                <?php foreach ($field['options'] ?? [] as $opt): ?>
                                    <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                                <?php endforeach; ?>
                            </select>

                        <?php elseif ($fType === 'checkbox'): ?>
                            <label class="form-checkbox">
                                <input type="checkbox" id="f-<?= e($fName) ?>" name="<?= e($fName) ?>" value="1" <?= $fReq ? 'required' : '' ?>>
                                <span style="font-size:0.8125rem;line-height:1.4;color:var(--text-secondary);"><?= e($fLabel) ?></span>
                            </label>

                        <?php else: ?>
                            <input class="form-input" type="<?= ($fType === 'phone' ? 'tel' : ($fType === 'email' ? 'email' : 'text')) ?>" id="f-<?= e($fName) ?>" name="<?= e($fName) ?>" placeholder="<?= e($fPlaceholder) ?>" <?= $fReq ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:1.5rem;">
                    Submit Enquiry &rarr;
                </button>
            </form>

            <div style="margin-top:2rem;text-align:center;font-size:0.75rem;color:var(--text-tertiary);">
                Protected with SSL encryption & spam verification • Powered by Intakely
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
