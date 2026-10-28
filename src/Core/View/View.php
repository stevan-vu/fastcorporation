<?php

declare(strict_types= 1);

namespace App\Core\View;

class View {
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = []): string
    {
        $file = \sprintf('%s/../../views/%s.php', __DIR__, $view);

        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract($data + self::$shared, EXTR_SKIP);

        ob_start();
        include $file;

        return (string) ob_get_clean();
    }
}
