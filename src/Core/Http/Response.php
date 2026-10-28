<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Response
{
    public function __construct(
        public readonly string $body,
        public readonly int $status = 200,
        public readonly string $contentType = 'text/html; charset=utf-8',
        public readonly array $headers = [],
    ) {}

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, 'text/html; charset=utf-8');
    }

    public static function redirect(string $location, int $status = 303): self
    {
        return new self('', $status, 'text/html; charset=utf-8', ['Location' => $location]);
    }

    public static function download(string $path, int $status = 200)
    {
        $file = dirname(__DIR__, 3) . '/'  . ltrim($path, '/');

        
        if (!is_file($file)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="dummy.exe"');
        header('Content-Length: ' . filesize($file));
        header('X-Content-Type-Options: nosniff');

        readfile($file);
        exit;
    }

    public function send(): void
    {
        http_response_code($this->status);
        header("Content-Type: $this->contentType");
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
    }
}
