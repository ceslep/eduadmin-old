<?php

require_once __DIR__ . '/Database.php';

class Matricula extends Model
{
    protected static string $table = 'matricula';

    public static function search(string $query): array
    {
        $stmt = self::$db->prepare(
            "SELECT * FROM matricula 
             WHERE nombres2 LIKE :q OR CAST(ind AS CHAR) LIKE :q2 
             ORDER BY nombres2 ASC 
             LIMIT 20"
        );
        $stmt->execute(['q' => "%$query%", 'q2' => "%$query%"]);
        return $stmt->fetchAll();
    }
}
