<?php
/**
 * Intakely — Response
 * HTTP response object with fluent API.
 */

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private int $statusCode;
    private string $body;
    private array $headers = [];

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body       = $body;
        $this->statusCode = $statusCode;
        $this->headers    = $headers;
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function view(string $template, array $data = [], int $status = 200): self
    {
        $container = Container::getInstance();
        $view = $container->has(View::class) ? $container->get(View::class) : new View();
        $html = $view->render($template, $data);
        return self::html($html, $status);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        return new self($body, $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function error(string $message, int $status = 500): self
    {
        return self::html(
            '<h1>' . htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8')
            . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>',
            $status
        );
    }

    public function withStatus(int $code): self
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        return $clone;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withBody(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Send the response to the client.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->body;
    }
}
