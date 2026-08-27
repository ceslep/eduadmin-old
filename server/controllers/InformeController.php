<?php

require_once __DIR__ . '/../models/Informe.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/Auth.php';

class InformeController
{
    public static function show(): void
    {
        Auth::authenticate();

        $matches = $_SERVER['REQUEST_URI_INFORMES'] ?? [];
        if (count($matches) < 3) {
            Response::error('Ruta inválida');
        }

        $estudiante = $matches[1];
        $anio = $matches[2];

        $informe = Informe::findByAnioEstudiante($anio, $estudiante);

        if ($informe === null) {
            Response::notFound('Informe no encontrado');
        }

        Response::success($informe);
    }
}
