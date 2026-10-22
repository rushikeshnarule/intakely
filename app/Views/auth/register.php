<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Start Free Trial — Intakely') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-full);
            padding: 2rem 1.5rem;
        }
        .auth-container {
            width: 100%;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div style="text-align:center;margin-bottom:2rem;">
            <a href="/" style="display:inline-flex;align-items:center;gap:0.75rem;text-decoration:none;">
                <div style="width:40px;height:40px;background:var(--accent-600);border-radius:var(--radius-xl);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1.125rem;">I</div>
                <span style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;color:var(--text-primary);">Intakely</span>
            </a>
            <h1 class="text-title-3" style="margin-top:1.25rem;">Start your 14-day free trial</h1>
            <p style="font-size:0.875rem;color:var(--text-secondary);margin-top:0.25rem;">No credit card required. Full access to all features.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background:var(--bg-danger);border:1px solid var(--border-danger);color:var(--text-danger);padding:0.75rem 1rem;border-radius:var(--radius-lg);margin-bottom:1.25rem;font-size:0.875rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="/register" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label" for="business_name">Business / Practice Name</label>
                        <input class="form-input" type="text" id="business_name" name="business_name" required placeholder="e.g. Sterling Legal LLP or City Dental">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="industry">Industry Template (Pre-configures your pipeline & automations)</label>
                        <select class="form-select" id="industry" name="industry" required>
                            <option value="law_firm" <?= ($industry === 'law_firm') ? 'selected' : '' ?>>Law Firm (Conveyancing, Wills, Litigation, Compliance Gates)</option>
                            <option value="dental_clinic" <?= ($industry === 'dental_clinic') ? 'selected' : '' ?>>Medical / Dental Clinic (Treatments, Consultations, No-Show Recovery)</option>
                            <option value="real_estate" <?= ($industry === 'real_estate') ? 'selected' : '' ?>>Real Estate Agency (Buyer/Seller, Viewings, Offers)</option>
                            <option value="digital_agency" <?= ($industry === 'digital_agency') ? 'selected' : '' ?>>Marketing & Digital Agency (Discovery, Retainers, Proposals)</option>
                            <option value="home_services" <?= ($industry === 'home_services') ? 'selected' : '' ?>>Home Services & Contractors (Jobs, Site Visits, Quotes)</option>
                            <option value="accountants" <?= ($industry === 'accountants') ? 'selected' : '' ?>>Accountants & Consultants (Accounts, Tax, Engagement Letters)</option>
                            <option value="blank" <?= ($industry === 'blank') ? 'selected' : '' ?>>Blank Canvas (Configure from scratch)</option>
                        </select>
                        <div class="form-hint">All pipeline stages and fields are fully editable after installation.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="owner_name">Your Full Name (Account Owner)</label>
                        <input class="form-input" type="text" id="owner_name" name="owner_name" required placeholder="e.g. Jane Doe">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Work Email Address</label>
                        <input class="form-input" type="email" id="email" name="email" required placeholder="jane@example.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password (Minimum 8 characters)</label>
                        <input class="form-input" type="password" id="password" name="password" minlength="8" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.75rem;">
                        Create My Account & Launch CRM
                    </button>
                </form>
            </div>
            <div class="card-footer" style="text-align:center;font-size:0.875rem;">
                Already have an account? <a href="/login">Sign in</a>
            </div>
        </div>
    </div>
</body>
</html>
