<?php

class Env
{
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) return;

        $path = dirname(__DIR__) . '/.env';

        if (!file_exists($path)) {
            throw new RuntimeException('.env file not found');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                self::$vars[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): ?string
    {
        self::load();
        return self::$vars[$key] ?? $default;
    }

    public static function require(string $key): string
    {
        self::load();
        if (!isset(self::$vars[$key])) {
            throw new RuntimeException("Missing required env variable: $key");
        }
        return self::$vars[$key];
    }
}
