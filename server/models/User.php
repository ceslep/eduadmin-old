<?php

require_once __DIR__ . '/Database.php';

class User extends Model
{
    protected static string $table = 'users';

    public static function findByUsername(string $username): ?array
    {
        return self::findBy('username', $username);
    }

    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', $email);
    }

    public static function verifyPassword(string $username, string $password): ?array
    {
        $user = self::findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }

        return null;
    }

    public static function createWithHash(array $data): array
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return self::create($data);
    }

    public static function getPublicData(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
