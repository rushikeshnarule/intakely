<?php
/**
 * Intakely — Marketing Site Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\BillingService;

final class MarketingController
{
    public function home(Request $request): Response
    {
        return Response::view('marketing/home', [
            'title' => 'Intakely — Never Lose a Lead',
            'plans' => BillingService::PLANS,
        ]);
    }

    public function pricing(Request $request): Response
    {
        return Response::view('marketing/pricing', [
            'title' => 'Pricing & Plans — Intakely',
            'plans' => BillingService::PLANS,
        ]);
    }

    public function features(Request $request): Response
    {
        return Response::view('marketing/features', [
            'title' => 'Features — Intakely',
        ]);
    }

    public function industry(Request $request): Response
    {
        $slug = $request->getAttribute('slug', 'law-firm');
        $industryNames = [
            'law-firm' => 'Law Firms & Legal Practices',
            'dental-clinic' => 'Medical & Dental Clinics',
            'real-estate' => 'Real Estate Agencies',
            'digital-agency' => 'Marketing & Digital Agencies',
            'home-services' => 'Home Services & Contractors',
            'accountants' => 'Accountants & Consultants',
        ];

        return Response::view('marketing/industry', [
            'slug' => $slug,
            'title' => ($industryNames[$slug] ?? 'Industry') . ' — Intakely',
            'industryName' => $industryNames[$slug] ?? 'Service Business',
        ]);
    }
}
