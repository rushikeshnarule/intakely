<?php
/**
 * Intakely — Form Builder & Embed Generator Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class FormController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $forms = $this->db->fetchAll(
            'SELECT * FROM forms WHERE tenant_id = $1 ORDER BY created_at DESC',
            [$tenantId]
        );

        return Response::view('forms/index', [
            'title' => 'Intake Forms — Intakely',
            'activeNav' => 'forms',
            'forms' => $forms,
        ]);
    }

    public function embed(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $formId = $request->getAttribute('id');

        $form = $this->db->fetchOne(
            'SELECT * FROM forms WHERE id = $1 AND tenant_id = $2',
            [$formId, $tenantId]
        );

        if (!$form) {
            flash('error', 'Form not found.');
            return Response::redirect('/forms');
        }

        $appUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $formUrl = "{$appUrl}/f/{$form['slug']}";

        return Response::view('forms/embed', [
            'title' => 'Embed Form: ' . $form['name'],
            'activeNav' => 'forms',
            'form' => $form,
            'formUrl' => $formUrl,
            'appUrl' => $appUrl,
            'tenantId' => $tenantId,
        ]);
    }
}
