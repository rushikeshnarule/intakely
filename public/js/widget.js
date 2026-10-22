/**
 * Intakely — Floating "Request a Call Back" Widget
 * Embeddable script for client websites
 */
(function() {
    'use strict';

    const scriptTag = document.currentScript || document.querySelector('script[data-tenant]');
    const tenantId = scriptTag ? scriptTag.getAttribute('data-tenant') : '';
    const apiUrl = scriptTag ? scriptTag.src.replace(/\/js\/widget\.js.*$/, '') : '';

    if (!tenantId) return;

    // Inject styles
    const style = document.createElement('style');
    style.innerHTML = `
        .intakely-widget-btn {
            position: fixed; bottom: 24px; right: 24px; z-index: 999999;
            background: #2563eb; color: #ffffff; padding: 12px 20px;
            border-radius: 9999px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px; font-weight: 600; cursor: pointer; border: none;
            display: flex; align-items: center; gap: 8px; transition: transform 0.2s;
        }
        .intakely-widget-btn:hover { transform: scale(1.04); }
        .intakely-modal-bg {
            position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1000000;
            display: none; align-items: center; justify-content: center; padding: 16px;
        }
        .intakely-modal-box {
            background: #ffffff; border-radius: 16px; padding: 24px; width: 100%; max-width: 380px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
        }
    `;
    document.head.appendChild(style);

    // Create button
    const btn = document.createElement('button');
    btn.className = 'intakely-widget-btn';
    btn.innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
        <span>Request a Call Back</span>
    `;
    document.body.appendChild(btn);

    // Create Modal
    const modal = document.createElement('div');
    modal.className = 'intakely-modal-bg';
    modal.innerHTML = `
        <div class="intakely-modal-box">
            <h3 style="margin:0 0 8px;font-size:18px;color:#111827;">Request a Free Callback</h3>
            <p style="margin:0 0 16px;font-size:13px;color:#4b5563;">Leave your details and a specialist will ring you within minutes.</p>
            <form id="intakely-cb-form">
                <input type="text" id="intakely-cb-name" placeholder="Your Name" required style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;margin-bottom:10px;font-size:14px;">
                <input type="tel" id="intakely-cb-phone" placeholder="Phone Number" required style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;margin-bottom:16px;font-size:14px;">
                <button type="submit" style="width:100%;background:#2563eb;color:#ffffff;border:none;padding:12px;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;">Call Me Back</button>
                <button type="button" id="intakely-cb-close" style="width:100%;background:none;border:none;padding:8px;color:#6b7280;cursor:pointer;font-size:12px;margin-top:4px;">Cancel</button>
            </form>
            <div id="intakely-cb-success" style="display:none;text-align:center;padding:16px 0;color:#059669;font-weight:600;">
                ✓ Request received! We will call you shortly.
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    btn.addEventListener('click', () => { modal.style.display = 'flex'; });
    modal.querySelector('#intakely-cb-close').addEventListener('click', () => { modal.style.display = 'none'; });
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

    modal.querySelector('#intakely-cb-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = modal.querySelector('#intakely-cb-name').value;
        const phone = modal.querySelector('#intakely-cb-phone').value;

        try {
            await fetch(`${apiUrl}/api/v1/public/callback`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tenant_id: tenantId, name: name, phone: phone })
            });
            modal.querySelector('#intakely-cb-form').style.display = 'none';
            modal.querySelector('#intakely-cb-success').style.display = 'block';
            setTimeout(() => { modal.style.display = 'none'; }, 3000);
        } catch (err) {
            alert('Unable to submit callback request.');
        }
    });
})();
