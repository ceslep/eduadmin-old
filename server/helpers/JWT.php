<?php

require_once __DIR__ . '/Env.php';

class JWT
{
    private static ?string $secret = null;
    private static ?int $expiration = null;

    private static function config(): void
    {
        if (self::$secret !== null) return;
        self::$secret     = Env::get('JWT_SECRET', 'eduadmin_jwt_secret_key_2024_ultra_secure');
        self::$expiration = (int) Env::get('JWT_EXPIRATION', '86400');
    }

    public static function encode(array $payload): string
    {
        self::config();
        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]));

        $payload['iat'] = time();
        $payload['exp'] = time() + self::$expiration;

        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", self::$secret, true)
        );

        return "$header.$payloadEncoded.$signature";
    }

    public static function decode(string $token): ?array
    {
        self::config();
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", self::$secret, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decoded = json_decode(self::base64UrlDecode($payload), true);
        if ($decoded === null) {
            return null;
        }

        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            return null;
        }

        return $decoded;
    }

    public static function getUserIdFromRequest(): ?int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);
        $payload = self::decode($token);

        if ($payload === null || !isset($payload['user_id'])) {
            return null;
        }

        return (int) $payload['user_id'];
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
