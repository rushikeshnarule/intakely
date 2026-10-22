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

        <!-- Chambers Demo Credentials Panel -->
        <div class="demo-panel" style="background:var(--bg-primary);border:1px solid var(--border-default);border-radius:var(--radius-xl);padding:1.25rem;margin-top:1.5rem;box-shadow:var(--shadow-sm);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:1rem;">⚖️</span>
                    <strong style="font-size:0.8125rem;color:var(--text-primary);letter-spacing:-0.01em;">Demo Chambers Credentials</strong>
                </div>
                <span class="badge badge-success" style="font-size:0.6875rem;">1-Click Auto-Fill</span>
            </div>
            
            <p style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:0.85rem;line-height:1.4;">
                Select any persona below to auto-fill credentials and sign into the live docket:
            </p>

            <div style="display:flex;flex-direction:column;gap:0.45rem;">
                <!-- Eleanor -->
                <button type="button" class="demo-user-btn" onclick="fillLogin('eleanor@example.com', 'password123')" style="padding:0.6rem 0.85rem;display:flex;align-items:center;justify-content:space-between;background:var(--bg-secondary);border:1px solid var(--border-default);border-radius:var(--radius-lg);cursor:pointer;">
                    <div style="text-align:left;">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="font-weight:700;font-size:0.8125rem;color:var(--text-primary);">Eleanor Vance</span>
                            <span class="badge badge-primary" style="font-size:0.65rem;">Managing Partner</span>
                        </div>
                        <div style="font-size:0.7188rem;color:var(--text-secondary);font-family:monospace;margin-top:0.2rem;">
                            eleanor@example.com <span style="color:var(--text-tertiary);">• password123</span>
                        </div>
                    </div>
                    <span style="font-size:0.75rem;font-weight:600;color:var(--accent-600);">&rarr; Enter</span>
                </button>

                <!-- David -->
                <button type="button" class="demo-user-btn" onclick="fillLogin('david@example.com', 'password123')" style="padding:0.6rem 0.85rem;display:flex;align-items:center;justify-content:space-between;background:var(--bg-secondary);border:1px solid var(--border-default);border-radius:var(--radius-lg);cursor:pointer;">
                    <div style="text-align:left;">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="font-weight:700;font-size:0.8125rem;color:var(--text-primary);">David Ross</span>
                            <span class="badge badge-neutral" style="font-size:0.65rem;">Associate Counsel</span>
                        </div>
                        <div style="font-size:0.7188rem;color:var(--text-secondary);font-family:monospace;margin-top:0.2rem;">
                            david@example.com <span style="color:var(--text-tertiary);">• password123</span>
                        </div>
                    </div>
                    <span style="font-size:0.75rem;font-weight:600;color:var(--accent-600);">&rarr; Enter</span>
                </button>

                <!-- Sarah -->
                <button type="button" class="demo-user-btn" onclick="fillLogin('sarah@example.com', 'password123')" style="padding:0.6rem 0.85rem;display:flex;align-items:center;justify-content:space-between;background:var(--bg-secondary);border:1px solid var(--border-default);border-radius:var(--radius-lg);cursor:pointer;">
                    <div style="text-align:left;">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="font-weight:700;font-size:0.8125rem;color:var(--text-primary);">Sarah Jenkins</span>
                            <span class="badge badge-neutral" style="font-size:0.65rem;">Senior Solicitor</span>
                        </div>
                        <div style="font-size:0.7188rem;color:var(--text-secondary);font-family:monospace;margin-top:0.2rem;">
                            sarah@example.com <span style="color:var(--text-tertiary);">• password123</span>
                        </div>
                    </div>
                    <span style="font-size:0.75rem;font-weight:600;color:var(--accent-600);">&rarr; Enter</span>
                </button>
            </div>
        </div>
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
