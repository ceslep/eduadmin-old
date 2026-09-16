<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/helpers/Env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/middleware/Auth.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/JWT.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/MatriculaController.php';
require_once __DIR__ . '/controllers/InformeController.php';

Model::init();

$rawUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$uri = rtrim($rawUri, '/');

// Strip /server prefix if present (Apache proxy)
if (preg_match('#^/server(.*)$#', $uri, $m)) {
    $uri = $m[1];
}
// Strip /eduadmin-old/server prefix if present (full path)
if (preg_match('#^/eduadmin-old/server(.*)$#', $uri, $m)) {
    $uri = $m[1];
}

$uri = '/' . ltrim($uri, '/');

$routes = [
    'POST /auth/login'       => 'AuthController::login',
    'POST /auth/register'    => 'AuthController::register',
    'GET /auth/profile'      => 'AuthController::profile',
    'POST /matricula/search' => 'MatriculaController::search',
];

$routeKey = "$method $uri";

if (isset($routes[$routeKey])) {
    $handler = $routes[$routeKey];
    $handler();
} elseif (preg_match('#^/informes/([^/]+)/([^/]+)/download$#', $uri, $m)) {
    $_SERVER['REQUEST_URI_INFORMES'] = $m;
    InformeController::download();
} elseif (preg_match('#^/informes/([^/]+)/([^/]+)$#', $uri, $m)) {
    $_SERVER['REQUEST_URI_INFORMES'] = $m;
    InformeController::show();
} else {
    Response::notFound('Endpoint no encontrado: ' . $routeKey);
}
