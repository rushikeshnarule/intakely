<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Sign In — Intakely') ?></title>
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
            padding: 1.5rem;
        }
        .auth-container {
            width: 100%;
            max-width: 440px;
        }
        .demo-panel {
            background: var(--bg-primary);
            border: 1px dashed var(--border-strong);
            border-radius: var(--radius-2xl);
            padding: 1.25rem;
            margin-top: 1.5rem;
        }
        .demo-user-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.35rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-default);
            border-radius: var(--radius-lg);
            cursor: pointer;
            text-align: left;
            transition: all var(--transition-fast);
        }
        .demo-user-btn:hover {
            background: var(--bg-accent);
            border-color: var(--accent-400);
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
            <h1 class="text-title-3" style="margin-top:1.25rem;">Sign in to your CRM</h1>
            <p style="font-size:0.875rem;color:var(--text-secondary);margin-top:0.25rem;">Enter your credentials to access your pipeline</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background:var(--bg-danger);border:1px solid var(--border-danger);color:var(--text-danger);padding:0.75rem 1rem;border-radius:var(--radius-lg);margin-bottom:1.25rem;font-size:0.875rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background:var(--bg-success);border:1px solid var(--border-success);color:var(--text-success);padding:0.75rem 1rem;border-radius:var(--radius-lg);margin-bottom:1.25rem;font-size:0.875rem;">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="/login" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label" for="email">Work Email Address</label>
                        <input class="form-input" type="email" id="email" name="email" required autofocus placeholder="name@example.com">
                    </div>

                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <label class="form-label" for="password">Password</label>
                            <a href="/forgot-password" style="font-size:0.75rem;">Forgot password?</a>
                        </div>
                        <input class="form-input" type="password" id="password" name="password" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">
                        Sign In
                    </button>
                </form>
            </div>
            <div class="card-footer" style="text-align:center;font-size:0.875rem;">
                Don't have an account? <a href="/register">Start 14-day free trial</a>
            </div>
        </div>

        <!-- Demo Mode Quick-Login Panel -->
        <?php if ($demoMode): ?>
            <div class="demo-panel">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                    <strong style="font-size:0.8125rem;color:var(--accent-700);text-transform:uppercase;letter-spacing:0.04em;">One-Click Demo Logins</strong>
                    <span class="badge badge-primary">Seed Data</span>
                </div>
                <div style="font-size:0.75rem;color:var(--text-tertiary);margin-bottom:0.75rem;">
                    Click any persona below to auto-fill credentials:
                </div>

                <div style="font-weight:600;font-size:0.75rem;margin-bottom:0.35rem;color:var(--text-primary);">Apex Law LLP (Law Firm Personas)</div>
                <button type="button" class="demo-user-btn" onclick="fillLogin('eleanor@example.com', 'password123')">
                    <div>
                        <div style="font-weight:600;font-size:0.8125rem;">Eleanor Vance</div>
                        <div style="font-size:0.6875rem;color:var(--text-tertiary);">Managing Partner (Full Access)</div>
                    </div>
                    <span class="badge badge-success">Owner</span>
                </button>
                <button type="button" class="demo-user-btn" onclick="fillLogin('david@example.com', 'password123')">
                    <div>
                        <div style="font-weight:600;font-size:0.8125rem;">David Ross</div>
                        <div style="font-size:0.6875rem;color:var(--text-tertiary);">Associate Counsel (Conveyancing)</div>
                    </div>
                    <span class="badge badge-neutral">Agent</span>
                </button>
                <button type="button" class="demo-user-btn" onclick="fillLogin('sarah@example.com', 'password123')">
                    <div>
                        <div style="font-weight:600;font-size:0.8125rem;">Sarah Jenkins</div>
                        <div style="font-size:0.6875rem;color:var(--text-tertiary);">Senior Solicitor (Commercial)</div>
                    </div>
                    <span class="badge badge-primary">Admin</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function fillLogin(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
