<?php

require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Response.php';

class Auth
{
    public static function authenticate(): int
    {
        $userId = JWT::getUserIdFromRequest();

        if ($userId === null) {
            Response::unauthorized('Token inválido o expirado');
        }

        return $userId;
    }

    public static function authenticateAs(string $role): int
    {
        $userId = self::authenticate();

        require_once __DIR__ . '/../models/User.php';
        Model::init();
        $user = User::find($userId);

        if ($user === null || $user['role'] !== $role) {
            Response::forbidden('No tienes permisos para esta acción');
        }

        return $userId;
    }
}
