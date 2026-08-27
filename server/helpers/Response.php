<?php

class Response
{
    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = ''): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    public static function error(string $message, int $statusCode = 400, mixed $errors = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        self::json($response, $statusCode);
    }

    public static function unauthorized(string $message = 'No autorizado'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Acceso denegado'): void
    {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'No encontrado'): void
    {
        self::error($message, 404);
    }
}
