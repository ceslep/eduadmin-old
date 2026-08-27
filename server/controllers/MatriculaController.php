<?php

require_once __DIR__ . '/../models/Matricula.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/Auth.php';

class MatriculaController
{
    public static function search(): void
    {
        Auth::authenticate();

        $input = json_decode(file_get_contents('php://input'), true);
        $query = $input['query'] ?? '';

        if (strlen($query) < 1) {
            Response::error('Query es requerido');
        }

        $results = Matricula::search($query);
        Response::success($results);
    }
}
