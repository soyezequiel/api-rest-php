<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// 1. El Autoload es lo primero: carga Slim y Dotenv
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/controllers/UserController.php';

// 2. Carga de variables de entorno (Indispensable para la HU-01)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 3. Requerir la clase DB después de cargar el entorno
require_once __DIR__ . '/models/DB.php'; 

$app = AppFactory::create();
$app->setBasePath((function () {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    return strpos($scriptName, 'index.php') !== false ? $scriptName : '';
})());
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// Configuración del ErrorMiddleware
$errorMiddleware = $app->addErrorMiddleware(($_ENV['APP_DEBUG'] ?? 'false') === 'true', true, true);

// Forzamos que los errores de Slim se devuelvan siempre como JSON
$errorMiddleware->setDefaultErrorHandler(function ($request, $exception, $displayErrorDetails) use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    
    $payload = [
        'status' => 'error',
        'message' => $exception->getMessage(),
    ];

    // Si estamos en modo debug, agregamos más info
    if ($displayErrorDetails) {
        $payload['trace'] = $exception->getTrace();
    }

    $response->getBody()->write(json_encode($payload));
    
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(500); // O el código que corresponda
});

$app->add( function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json')
    ;
});

// ACÁ VAN LOS ENDPOINTS
$app->get('/test-env', function (Request $request, Response $response) {
    $data = [
        'status' => 'success',
        'message' => 'Entorno configurado correctamente',
        'db_host_variable' => $_ENV['DB_HOST']
    ];
    $response->getBody()->write(json_encode($data));
    return $response;
});

$app->post('/usuarios', [UserController::class, 'registrar']);
$app->post('/login', [UserController::class, 'login']);

$app->run();

