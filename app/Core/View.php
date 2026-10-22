<?php
/**
 * Intakely — View / Template Renderer
 *
 * Simple PHP template engine with layouts, sections, and includes.
 * Templates live in app/Views/ with .php extension.
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /** Base directory for view templates. */
    private string $viewsPath;

    /** Layout template name (set by the child view). */
    private ?string $layoutName = null;

    /** @var array<string, string> Captured section contents */
    private array $sections = [];

    /** Name of the section currently being captured. */
    private ?string $currentSection = null;

    /** @var array<string, mixed> Data for the current render */
    private array $data = [];

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? (defined('APP_PATH') ? APP_PATH . '/Views' : __DIR__ . '/../Views');
    }

    // ── Public API ──────────────────────────────────────────────

    /**
     * Render a template with data and return the HTML string.
     *
     * @param string               $template  Dot or slash path relative to Views/
     * @param array<string, mixed> $data      Variables to extract into the template
     */
    public function render(string $template, array $data = []): string
    {
        $this->data = $data;
        $this->layoutName = null;
        $this->sections = [];

        // Render the child template first
        $childContent = $this->renderTemplate($template, $data);

        // If the child declared a layout, render the layout with sections
        if ($this->layoutName !== null) {
            // The child's non-section output becomes the 'content' section fallback
            if (!isset($this->sections['content'])) {
                $this->sections['content'] = $childContent;
            }
            return $this->renderTemplate($this->layoutName, $data);
        }

        return $childContent;
    }

    // ── Template Directives (called from within templates) ──────

    /**
     * Declare that this view extends a layout.
     *
     * @param string $layout  Layout template name (e.g. "layouts/app")
     */
    public function layout(string $layout): void
    {
        $this->layoutName = $layout;
    }

    /**
     * Begin capturing a named section.
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End the current section capture.
     */
    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new RuntimeException('endSection() called without a matching section().');
        }

        $this->sections[$this->currentSection] = ob_get_clean() ?: '';
        $this->currentSection = null;
    }

    /**
     * Output (yield) a captured section within a layout.
     *
     * @param string $name     Section name
     * @param string $default  Fallback if section was not defined
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Include a partial template.
     *
     * @param string               $template  Partial template name
     * @param array<string, mixed> $extraData Additional data for the partial
     */
    public function include(string $template, array $extraData = []): void
    {
        echo $this->renderTemplate($template, array_merge($this->data, $extraData));
    }

    // ── Internals ───────────────────────────────────────────────

    /**
     * Resolve a template name to a file path.
     */
    private function resolvePath(string $template): string
    {
        // Support dot notation: "layouts.app" → "layouts/app"
        $template = str_replace('.', '/', $template);
        $path = $this->viewsPath . '/' . $template . '.php';

        if (!file_exists($path)) {
            throw new RuntimeException("View [{$template}] not found at [{$path}].");
        }

        return $path;
    }

    /**
     * Render a single template file and return its output.
     *
     * @param array<string, mixed> $data
     */
    private function renderTemplate(string $template, array $data): string
    {
        $__path = $this->resolvePath($template);

        // Extract data as local variables; $this is available for directives
        extract($data, EXTR_SKIP);

        ob_start();

        try {
            require $__path;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean() ?: '';
    }
}
