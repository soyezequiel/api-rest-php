<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;

use App\Middleware\AuthMiddleware;
use App\Controllers\UserController;

require_once __DIR__ . '/vendor/autoload.php'; //Carga automáticamente todas las librerías externas (Slim, JWT, Dotenv) instaladas vía Composer

date_default_timezone_set('America/Argentina/Buenos_Aires');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$app = AppFactory::create();
$app->setBasePath("");
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(($_ENV['APP_DEBUG'] ?? 'false') === 'true', true, true);

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

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json')
    ;
});

// ACÁ VAN LOS ENDPOINTS
// Endpoint de prueba para verificar que el entorno se carga correctamente
$app->get('/test-env', function (Request $request, Response $response) {
    $data = [
        'status' => 'success',
        'message' => 'Entorno configurado correctamente',
        'db_host_variable' => $_ENV['DB_HOST']
    ];
    $response->getBody()->write(json_encode($data));
    return $response;
});

// Rutas de autenticación
$app->post('/users', [UserController::class, 'registrar']);
$app->post('/login', [UserController::class, 'login']);

// Manejador para rutas no encontradas
$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function ($request, $exception, $displayErrorDetails) use ($app) {
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write(json_encode([
            "status" => "error",
            "message" => "Ruta no encontrada (404)"
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
);

// Grupo de rutas protegidas (aquí irán el portfolio, compra/venta, etc.)
$app->group('/api', function ($group) {

    // Ruta de prueba para verificar el Middleware
    $group->get('/test-auth', function ($request, $response) {
        $userId = $request->getAttribute('user_id');
        $response->getBody()->write(json_encode([
            "status" => "success",
            "message" => "Acceso concedido para el usuario ID: " . $userId
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    //Acá agregamos rutas protegidas, por ejemplo:
    // $group->get('/portfolio', [PortfolioController::class, 'getPortfolio']);

    // Rutas de usuario
    $group->post('/logout', [UserController::class, 'logout']);

})->add(new AuthMiddleware());

$app->run();
