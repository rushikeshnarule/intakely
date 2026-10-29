<?php
/**
 * Intakely — Route definitions
 * $router is available from the closure in bootstrap.php
 */

declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */

// ============================================================
// PUBLIC ROUTES (no auth required)
// ============================================================

// Admin / Ops Setup (protected by APP_KEY)
$router->get('/admin/setup-db', function ($request) {
    $key = $request->query('key');
    $appKey = env('APP_KEY', 'secret');
    if (empty($key) || $key !== $appKey) {
        return \App\Core\Response::html('Unauthorized. Please provide valid ?key= parameter.', 403);
    }
    ob_start();
    try {
        echo "=== Running Migrations ===\n";
        require BASE_PATH . '/bin/migrate.php';
        echo "\n=== Running Seeders ===\n";
        require BASE_PATH . '/bin/seed.php';
    } catch (\Throwable $e) {
        echo "\n[ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString();
    }
    $output = ob_get_clean();
    return \App\Core\Response::html("<pre style='background:#0d1117;color:#38bdf8;padding:2rem;font-family:monospace;white-space:pre-wrap;'>" . e($output) . "</pre>");
});

// Marketing site
$router->get('/', 'MarketingController@home');
$router->get('/pricing', 'MarketingController@pricing');
$router->get('/features', 'MarketingController@features');
$router->get('/industry/{slug}', 'MarketingController@industry');

// Auth
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/verify-email/{token}', 'AuthController@verifyEmail');
$router->get('/forgot-password', 'AuthController@showForgotPassword');
$router->post('/forgot-password', 'AuthController@forgotPassword');
$router->get('/reset-password/{token}', 'AuthController@showResetPassword');
$router->post('/reset-password/{token}', 'AuthController@resetPassword');
$router->post('/logout', 'AuthController@logout');

// Public forms
$router->get('/f/{slug}', 'PublicFormController@show');
$router->post('/f/{slug}', 'PublicFormController@submit');

// Public booking
$router->get('/book/{user}', 'PublicBookingController@show');
$router->post('/book/{user}', 'PublicBookingController@book');

// Callback widget endpoint
$router->post('/api/v1/public/callback', 'PublicFormController@callback');

// Inbound webhooks (no CSRF, auth via signatures)
$router->post('/api/v1/webhooks/email', 'WebhookController@email');
$router->post('/api/v1/webhooks/whatsapp', 'WebhookController@whatsapp');
$router->post('/api/v1/webhooks/sms', 'WebhookController@sms');
$router->post('/api/v1/webhooks/facebook', 'WebhookController@facebook');
$router->post('/api/v1/webhooks/google-ads', 'WebhookController@googleAds');
$router->post('/api/v1/webhooks/stripe', 'WebhookController@stripe');
$router->post('/api/v1/webhooks/generic/{key}', 'WebhookController@generic');

// SSE endpoint
$router->get('/sse/leads', 'SseController@leads');

// ============================================================
// AUTHENTICATED TENANT ROUTES
// ============================================================

// Dashboard
$router->get('/dashboard', 'DashboardController@index');
$router->get('/api/v1/dashboard/stats', 'DashboardController@stats');

// Pipeline / Kanban
$router->get('/pipeline', 'PipelineController@index');
$router->get('/pipeline/{id}', 'PipelineController@show');
$router->post('/pipeline', 'PipelineController@store');
$router->post('/pipeline/{id}', 'PipelineController@update');
$router->post('/pipeline/{id}/delete', 'PipelineController@destroy');

// Pipeline stages
$router->post('/pipeline/{pipelineId}/stages', 'StageController@store');
$router->post('/stages/{id}', 'StageController@update');
$router->post('/stages/{id}/delete', 'StageController@destroy');

// Leads
$router->get('/leads', 'LeadController@index');
$router->get('/leads/create', 'LeadController@create');
$router->post('/leads', 'LeadController@store');
$router->get('/leads/{id}', 'LeadController@show');
$router->post('/leads/{id}', 'LeadController@update');
$router->post('/leads/{id}/delete', 'LeadController@destroy');
$router->post('/leads/{id}/stage', 'LeadController@updateStage');
$router->post('/leads/{id}/quick-update', 'LeadController@quickUpdate');
$router->post('/leads/{id}/assign', 'LeadController@assign');
$router->post('/leads/{id}/score', 'LeadController@updateScore');
$router->post('/leads/{id}/note', 'LeadController@addNote');
$router->post('/leads/{id}/task', 'LeadController@addTask');
$router->post('/leads/{id}/reply', 'LeadController@reply');
$router->post('/leads/{id}/tag', 'LeadController@addTag');
$router->post('/leads/{id}/merge', 'LeadController@merge');
$router->get('/leads/{id}/timeline', 'LeadController@timeline');
$router->get('/leads/search', 'LeadController@search');
$router->get('/leads/export', 'LeadController@export');
$router->post('/leads/import', 'LeadController@import');
$router->post('/leads/bulk', 'LeadController@bulk');

// Board API (drag-and-drop)
$router->get('/api/v1/board/{pipelineId}', 'BoardController@index');
$router->post('/api/v1/board/move', 'BoardController@move');

// Forms
$router->get('/forms', 'FormController@index');
$router->get('/forms/create', 'FormController@create');
$router->post('/forms', 'FormController@store');
$router->get('/forms/{id}', 'FormController@show');
$router->get('/forms/{id}/edit', 'FormController@edit');
$router->post('/forms/{id}', 'FormController@update');
$router->post('/forms/{id}/delete', 'FormController@destroy');
$router->get('/forms/{id}/embed', 'FormController@embed');
$router->get('/forms/{id}/submissions', 'FormController@submissions');

// Automations
$router->get('/automations', 'AutomationController@index');
$router->get('/automations/create', 'AutomationController@create');
$router->post('/automations', 'AutomationController@store');
$router->get('/automations/{id}', 'AutomationController@show');
$router->post('/automations/{id}', 'AutomationController@update');
$router->post('/automations/{id}/delete', 'AutomationController@destroy');
$router->post('/automations/{id}/toggle', 'AutomationController@toggle');
$router->get('/automations/{id}/logs', 'AutomationController@logs');

// Email templates
$router->get('/templates', 'TemplateController@index');
$router->get('/templates/create', 'TemplateController@create');
$router->post('/templates', 'TemplateController@store');
$router->get('/templates/{id}', 'TemplateController@show');
$router->post('/templates/{id}', 'TemplateController@update');
$router->post('/templates/{id}/delete', 'TemplateController@destroy');

// Tasks
$router->get('/tasks', 'TaskController@index');
$router->post('/tasks', 'TaskController@store');
$router->post('/tasks/{id}', 'TaskController@update');
$router->post('/tasks/{id}/complete', 'TaskController@complete');
$router->post('/tasks/{id}/delete', 'TaskController@destroy');

// Appointments
$router->get('/appointments', 'AppointmentController@index');
$router->post('/appointments', 'AppointmentController@store');
$router->post('/appointments/{id}', 'AppointmentController@update');
$router->post('/appointments/{id}/status', 'AppointmentController@updateStatus');
$router->post('/appointments/{id}/delete', 'AppointmentController@destroy');
$router->get('/appointments/calendar', 'AppointmentController@calendar');
$router->get('/appointments/{id}/ics', 'AppointmentController@ics');

// Contacts & Companies
$router->get('/contacts', 'ContactController@index');
$router->post('/contacts', 'ContactController@store');
$router->get('/companies', 'CompanyController@index');
$router->post('/companies', 'CompanyController@store');

// Analytics
$router->get('/analytics', 'AnalyticsController@index');
$router->get('/api/v1/analytics/overview', 'AnalyticsController@overview');
$router->get('/api/v1/analytics/pipeline', 'AnalyticsController@pipeline');
$router->get('/api/v1/analytics/sources', 'AnalyticsController@sources');
$router->get('/api/v1/analytics/team', 'AnalyticsController@team');
$router->get('/api/v1/analytics/trends', 'AnalyticsController@trends');
$router->get('/analytics/export', 'AnalyticsController@export');

// Routing rules
$router->get('/settings/routing', 'RoutingController@index');
$router->post('/settings/routing', 'RoutingController@store');
$router->post('/settings/routing/{id}', 'RoutingController@update');
$router->post('/settings/routing/{id}/delete', 'RoutingController@destroy');

// Scoring rules
$router->get('/settings/scoring', 'ScoringController@index');
$router->post('/settings/scoring', 'ScoringController@store');
$router->post('/settings/scoring/{id}', 'ScoringController@update');

// Settings
$router->get('/settings', 'SettingsController@index');
$router->post('/settings', 'SettingsController@update');
$router->get('/settings/team', 'SettingsController@team');
$router->post('/settings/team/invite', 'SettingsController@invite');
$router->post('/settings/team/{id}', 'SettingsController@updateUser');
$router->post('/settings/team/{id}/delete', 'SettingsController@removeUser');
$router->get('/settings/roles', 'SettingsController@roles');
$router->post('/settings/roles', 'SettingsController@createRole');
$router->get('/settings/billing', 'BillingController@index');
$router->post('/settings/billing/checkout', 'BillingController@checkout');
$router->get('/settings/billing/portal', 'BillingController@portal');
$router->get('/settings/api-keys', 'ApiKeyController@index');
$router->post('/settings/api-keys', 'ApiKeyController@store');
$router->post('/settings/api-keys/{id}/delete', 'ApiKeyController@destroy');
$router->get('/settings/webhooks', 'WebhookSettingsController@index');
$router->post('/settings/webhooks', 'WebhookSettingsController@store');
$router->get('/settings/compliance', 'ComplianceController@index');
$router->get('/settings/audit-log', 'AuditLogController@index');
$router->post('/settings/data-export', 'ComplianceController@export');
$router->post('/settings/data-erasure', 'ComplianceController@erasure');

// AI Assistant
$router->post('/api/v1/ai/summarize', 'AiController@summarize');
$router->post('/api/v1/ai/suggest-action', 'AiController@suggestAction');
$router->post('/api/v1/ai/draft-reply', 'AiController@draftReply');
$router->post('/api/v1/ai/categorize', 'AiController@categorize');
$router->post('/api/v1/ai/translate', 'AiController@translate');

// Notifications
$router->get('/api/v1/notifications', 'NotificationController@index');
$router->post('/api/v1/notifications/{id}/read', 'NotificationController@markRead');
$router->post('/api/v1/notifications/read-all', 'NotificationController@markAllRead');

// ============================================================
// SUPER-ADMIN ROUTES (separate auth, audited)
// ============================================================

$router->get('/admin/login', 'Admin\AdminAuthController@showLogin');
$router->post('/admin/login', 'Admin\AdminAuthController@login');
$router->get('/admin', 'Admin\AdminDashboardController@index');
$router->get('/admin/tenants', 'Admin\TenantManagementController@index');
$router->get('/admin/tenants/{id}', 'Admin\TenantManagementController@show');
$router->post('/admin/tenants/{id}/impersonate', 'Admin\TenantManagementController@impersonate');
$router->post('/admin/tenants/{id}/plan', 'Admin\TenantManagementController@updatePlan');
$router->get('/admin/metrics', 'Admin\MetricsController@index');
$router->get('/admin/health', 'Admin\HealthController@index');
$router->post('/admin/jobs/run', 'Admin\JobController@runNow');
$router->get('/admin/jobs', 'Admin\JobController@index');
$router->get('/admin/feature-flags', 'Admin\FeatureFlagController@index');
$router->post('/admin/feature-flags', 'Admin\FeatureFlagController@update');

// ============================================================
// REST API v1 (bearer token auth)
// ============================================================

$router->get('/api/v1/leads', 'Api\LeadApiController@index');
$router->post('/api/v1/leads', 'Api\LeadApiController@store');
$router->get('/api/v1/leads/{id}', 'Api\LeadApiController@show');
$router->post('/api/v1/leads/{id}', 'Api\LeadApiController@update');
$router->get('/api/v1/pipelines', 'Api\PipelineApiController@index');
$router->get('/api/v1/forms', 'Api\FormApiController@index');
