/**
 * Intakely — Core JavaScript
 * Vanilla JS utilities, fetch helpers, UI interactions
 */

'use strict';

const Intakely = {
    // ============================================================
    // CSRF & FETCH
    // ============================================================
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

    async request(url, options = {}) {
        const defaults = {
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        if (options.body && !(options.body instanceof FormData)) {
            defaults.headers['Content-Type'] = 'application/json';
            if (typeof options.body === 'object') {
                options.body = JSON.stringify(options.body);
            }
        }

        const config = { ...defaults, ...options };
        config.headers = { ...defaults.headers, ...options.headers };

        try {
            const response = await fetch(url, config);
            if (response.status === 419) {
                Toast.error('Session expired. Please refresh the page.');
                return null;
            }
            if (response.status === 401) {
                window.location.href = '/login';
                return null;
            }
            return response;
        } catch (error) {
            console.error('Request failed:', error);
            Toast.error('Network error. Please check your connection.');
            return null;
        }
    },

    async get(url) {
        return this.request(url, { method: 'GET' });
    },

    async post(url, data = {}) {
        return this.request(url, { method: 'POST', body: data });
    },

    // ============================================================
    // TOAST NOTIFICATIONS
    // ============================================================
    Toast: null,

    // ============================================================
    // DARK MODE
    // ============================================================
    initDarkMode() {
        const stored = localStorage.getItem('intakely-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (stored === 'dark' || (!stored && prefersDark)) {
            document.documentElement.classList.add('dark');
        }

        const toggle = document.getElementById('dark-mode-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('intakely-theme', isDark ? 'dark' : 'light');
            });
        }
    },

    // ============================================================
    // DROPDOWNS
    // ============================================================
    initDropdowns() {
        document.addEventListener('click', (e) => {
            // Close all dropdowns when clicking outside
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.active').forEach(m => {
                    m.classList.remove('active');
                });
            }
        });

        document.querySelectorAll('[data-dropdown]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const menu = trigger.closest('.dropdown').querySelector('.dropdown-menu');
                
                // Close other dropdowns
                document.querySelectorAll('.dropdown-menu.active').forEach(m => {
                    if (m !== menu) m.classList.remove('active');
                });
                
                menu.classList.toggle('active');
            });
        });
    },

    // ============================================================
    // MODALS
    // ============================================================
    openModal(id) {
        const backdrop = document.getElementById(id);
        if (backdrop) {
            backdrop.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Close on backdrop click
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) this.closeModal(id);
            });

            // Close on Escape
            const handler = (e) => {
                if (e.key === 'Escape') {
                    this.closeModal(id);
                    document.removeEventListener('keydown', handler);
                }
            };
            document.addEventListener('keydown', handler);
        }
    },

    closeModal(id) {
        const backdrop = document.getElementById(id);
        if (backdrop) {
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    // ============================================================
    // CONFIRM DIALOG
    // ============================================================
    confirm(message, onConfirm) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop active';
        backdrop.innerHTML = `
            <div class="modal" style="max-width: 400px;">
                <div class="modal-body confirm-dialog">
                    <div class="confirm-dialog-icon danger">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-title-3" style="margin-bottom: var(--space-2);">Are you sure?</h3>
                    <p class="text-body" style="color: var(--text-secondary);">${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-cancel>Cancel</button>
                    <button class="btn btn-danger" data-confirm>Confirm</button>
                </div>
            </div>
        `;

        document.body.appendChild(backdrop);

        backdrop.querySelector('[data-cancel]').addEventListener('click', () => {
            backdrop.remove();
        });

        backdrop.querySelector('[data-confirm]').addEventListener('click', () => {
            backdrop.remove();
            onConfirm();
        });

        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) backdrop.remove();
        });
    },

    // ============================================================
    // SIDEBAR
    // ============================================================
    initSidebar() {
        const toggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const mobileToggle = document.getElementById('mobile-sidebar-toggle');

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });

            // Restore state
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
            }
        }

        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
            });
        }
    },

    // ============================================================
    // SSE (Server-Sent Events for live updates)
    // ============================================================
    sse: null,

    initSSE() {
        if (typeof EventSource === 'undefined') return;

        this.sse = new EventSource('/sse/leads');

        this.sse.addEventListener('lead_created', (e) => {
            const data = JSON.parse(e.data);
            Toast.success(`New lead: ${data.name}`);
            document.dispatchEvent(new CustomEvent('intakely:lead_created', { detail: data }));
        });

        this.sse.addEventListener('lead_moved', (e) => {
            const data = JSON.parse(e.data);
            document.dispatchEvent(new CustomEvent('intakely:lead_moved', { detail: data }));
        });

        this.sse.addEventListener('lead_assigned', (e) => {
            const data = JSON.parse(e.data);
            document.dispatchEvent(new CustomEvent('intakely:lead_assigned', { detail: data }));
        });

        this.sse.addEventListener('notification', (e) => {
            const data = JSON.parse(e.data);
            Toast.info(data.message);
            this.updateNotificationBadge();
        });

        this.sse.onerror = () => {
            console.log('SSE connection lost, reconnecting...');
        };
    },

    updateNotificationBadge() {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            const count = parseInt(badge.textContent || '0') + 1;
            badge.textContent = count;
            badge.classList.remove('hidden');
        }
    },

    // ============================================================
    // SLA TIMER
    // ============================================================
    initSlaTimers() {
        const timers = document.querySelectorAll('[data-sla-deadline]');
        
        const updateTimers = () => {
            timers.forEach(el => {
                const deadline = new Date(el.dataset.slaDeadline);
                const now = new Date();
                const diff = deadline - now;

                if (diff <= 0) {
                    el.className = 'sla-timer breached';
                    el.textContent = 'SLA Breached';
                    return;
                }

                const hours = Math.floor(diff / 3600000);
                const minutes = Math.floor((diff % 3600000) / 60000);

                if (hours < 1) {
                    el.className = 'sla-timer warning';
                    el.textContent = `${minutes}m left`;
                } else {
                    el.className = 'sla-timer ok';
                    el.textContent = `${hours}h ${minutes}m left`;
                }
            });
        };

        updateTimers();
        setInterval(updateTimers, 60000); // Update every minute
    },

    // ============================================================
    // SEARCH
    // ============================================================
    initSearch() {
        const input = document.getElementById('global-search');
        if (!input) return;

        let debounceTimer;
        const results = document.getElementById('search-results');

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                const query = input.value.trim();
                if (query.length < 2) {
                    if (results) results.classList.add('hidden');
                    return;
                }

                const resp = await this.get(`/leads/search?q=${encodeURIComponent(query)}`);
                if (resp && resp.ok) {
                    const data = await resp.json();
                    if (results) {
                        results.innerHTML = data.html || '';
                        results.classList.remove('hidden');
                    }
                }
            }, 300);
        });

        // Close search on click outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-wrapper') && results) {
                results.classList.add('hidden');
            }
        });

        // Keyboard shortcut: Ctrl+K or Cmd+K
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                input.focus();
            }
        });
    },

    // ============================================================
    // TABS
    // ============================================================
    initTabs() {
        document.querySelectorAll('.tabs').forEach(tabGroup => {
            tabGroup.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    // Deactivate all
                    tabGroup.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    // Show target panel
                    const target = tab.dataset.tab;
                    if (target) {
                        const parent = tabGroup.closest('.tab-container') || tabGroup.parentElement;
                        parent.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                        const panel = parent.querySelector(`#${target}`);
                        if (panel) panel.classList.remove('hidden');
                    }
                });
            });
        });
    },

    // ============================================================
    // FORMAT HELPERS
    // ============================================================
    formatCurrency(amount, currency = 'GBP') {
        return new Intl.NumberFormat('en-GB', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    },

    formatDate(dateStr) {
        const date = new Date(dateStr);
        return new Intl.DateTimeFormat('en-GB', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }).format(date);
    },

    formatRelativeTime(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        return this.formatDate(dateStr);
    },

    // ============================================================
    // INIT
    // ============================================================
    init() {
        this.initDarkMode();
        this.initDropdowns();
        this.initSidebar();
        this.initSearch();
        this.initSlaTimers();
        this.initTabs();
        
        // Init SSE only on authenticated pages
        if (document.body.dataset.authenticated === 'true') {
            this.initSSE();
        }
    },
};

// ============================================================
// TOAST CLASS
// ============================================================

class Toast {
    static container = null;

    static getContainer() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
        return this.container;
    }

    static show(message, type = 'info', duration = 4000) {
        const container = this.getContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        };

        toast.innerHTML = `
            <span style="flex-shrink:0;color:var(--text-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'accent'})">${icons[type] || icons.info}</span>
            <span class="grow text-body-medium">${message}</span>
            <button class="btn-icon btn-ghost" onclick="this.closest('.toast').remove()" style="flex-shrink:0" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;

        container.appendChild(toast);
        
        // Trigger animation
        requestAnimationFrame(() => toast.classList.add('show'));

        // Auto-remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    static success(msg) { this.show(msg, 'success'); }
    static error(msg) { this.show(msg, 'error', 6000); }
    static warning(msg) { this.show(msg, 'warning'); }
    static info(msg) { this.show(msg, 'info'); }
}

// Make Toast available globally
window.Toast = Toast;
Intakely.Toast = Toast;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => Intakely.init());
