<?php

class Model
{
    protected static PDO $db;
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function init(): void
    {
        self::$db = Database::getConnection();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::$db->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        $stmt = self::$db->prepare("SELECT * FROM " . static::$table . " WHERE $column = :value LIMIT 1");
        $stmt->execute(['value' => $value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function all(): array
    {
        $stmt = self::$db->query("SELECT * FROM " . static::$table);
        return $stmt->fetchAll();
    }

    public static function create(array $data): array
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $stmt = self::$db->prepare("INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders)");
        $stmt->execute($data);

        $data[static::$primaryKey] = (int) self::$db->lastInsertId();
        return $data;
    }

    public static function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($col) => "$col = :$col", array_keys($data)));

        $stmt = self::$db->prepare("UPDATE " . static::$table . " SET $set WHERE " . static::$primaryKey . " = :id");
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::$db->prepare("DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id");
        return $stmt->execute(['id' => $id]);
    }

    public static function count(): int
    {
        $stmt = self::$db->query("SELECT COUNT(*) FROM " . static::$table);
        return (int) $stmt->fetchColumn();
    }
}
