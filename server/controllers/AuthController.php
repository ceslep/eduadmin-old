<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Response.php';

class AuthController
{
    public static function login(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['username']) || empty($input['password'])) {
            Response::error('Username y password son requeridos');
        }

        $user = User::verifyPassword($input['username'], $input['password']);

        if ($user === null) {
            Response::error('Credenciales inválidas', 401);
        }

        $token = JWT::encode([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role'    => $user['role'],
        ]);

        Response::success([
            'token' => $token,
            'user'  => $user,
        ], 'Login exitoso');
    }

    public static function register(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['username']) || empty($input['email']) || empty($input['password'])) {
            Response::error('Username, email y password son requeridos');
        }

        if (User::findByUsername($input['username'])) {
            Response::error('El username ya está en uso');
        }

        if (User::findByEmail($input['email'])) {
            Response::error('El email ya está registrado');
        }

        $user = User::createWithHash([
            'username' => $input['username'],
            'email'    => $input['email'],
            'password' => $input['password'],
            'role'     => $input['role'] ?? 'alumno',
        ]);

        $token = JWT::encode([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role'    => $user['role'],
        ]);

        Response::success([
            'token' => $token,
            'user'  => User::getPublicData($user),
        ], 'Registro exitoso', 201);
    }

    public static function profile(): void
    {
        $userId = Auth::authenticate();

        $user = User::find($userId);

        if ($user === null) {
            Response::notFound('Usuario no encontrado');
        }

        Response::success(User::getPublicData($user));
    }
}
