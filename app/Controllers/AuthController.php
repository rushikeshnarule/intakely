<?php
/**
 * Intakely — Auth Controller
 * Handles user login with demo credentials switch, registration with template choice,
 * and session sign out.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\TenantService;

final class AuthController
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? new AuthService();
    }

    public function showLogin(Request $request): Response
    {
        // If already logged in, redirect to pipeline
        if (isset($_SESSION['_user_id'])) {
            return Response::redirect('/pipeline');
        }

        return Response::view('auth/login', [
            'title' => 'Sign In — Intakely',
            'error' => old_flash('error'),
            'success' => old_flash('success'),
            'demoMode' => config('app.demo_mode', true),
        ]);
    }

    public function login(Request $request): Response
    {
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        $tenantId = $request->getAttribute('_tenant_id');

        if (empty($email) || empty($password)) {
            flash('error', 'Please enter your email and password.');
            return Response::redirect('/login');
        }

        $result = $this->auth->login($email, $password, $tenantId);

        if (!$result['success']) {
            flash('error', $result['error'] ?? 'Invalid email or password.');
            return Response::redirect('/login');
        }

        $redirect = $_SESSION['_intended_url'] ?? '/pipeline';
        unset($_SESSION['_intended_url']);

        return Response::redirect($redirect);
    }

    public function showRegister(Request $request): Response
    {
        return Response::view('auth/register', [
            'title' => 'Create Organization — Intakely',
            'industry' => $request->query('industry', 'law_firm'),
            'plan' => $request->query('plan', 'growth'),
            'error' => old_flash('error'),
        ]);
    }

    public function register(Request $request): Response
    {
        $businessName = trim((string) $request->input('business_name', ''));
        $ownerName = trim((string) $request->input('owner_name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $industry = (string) $request->input('industry', 'law_firm');

        if (empty($businessName) || empty($ownerName) || empty($email) || strlen($password) < 8) {
            flash('error', 'All fields are required. Password must be at least 8 characters.');
            return Response::redirect('/register');
        }

        try {
            $result = $this->auth->register($businessName, $ownerName, $email, $password, $industry);
            return Response::redirect('/pipeline');
        } catch (\Throwable $e) {
            flash('error', 'Registration error: ' . $e->getMessage());
            return Response::redirect('/register');
        }
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::redirect('/login');
    }
}
