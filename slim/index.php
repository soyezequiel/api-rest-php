<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;

use App\Controllers\AssetController;
use App\Controllers\PortfolioController;
use App\Controllers\TradeController;
use App\Controllers\TransactionController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

require_once __DIR__ . '/vendor/autoload.php'; //Carga automáticamente todas las librerías externas (Slim, JWT, Dotenv) instaladas vía Composer

date_default_timezone_set('America/Argentina/Buenos_Aires');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
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

    if ($displayErrorDetails) {
        $payload['trace'] = $exception->getTrace();
    }

    $response->getBody()->write(json_encode($payload));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(500);
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json');
});


// Rutas de usuario y autenticación
$app->post('/users', [UserController::class, 'registrar']);
$app->post('/login', [UserController::class, 'login']);
$app->post('/logout', [UserController::class, 'logout'])->add(new AuthMiddleware());

$app->get('/assets', [AssetController::class, 'listar']);
$app->put('/assets', [AssetController::class, 'actualizarPrecio'])->add(new AuthMiddleware());
$app->get('/assets/{asset_id}/history/{quantity}', [AssetController::class, 'consultarHistorial']);

$app->get('/portfolio', [PortfolioController::class, 'obtenerPortafolio'])->add(new AuthMiddleware());
$app->delete('/portfolio/{asset_id}', [PortfolioController::class, 'borrarPortafolio'])->add(new AuthMiddleware());

$app->post('/trade/buy', [TradeController::class, 'buy'])->add(new AuthMiddleware());
$app->post('/trade/sell', [TradeController::class, 'vender'])->add(new AuthMiddleware());

$app->get('/transactions', [TransactionController::class, 'obtenerTransacciones'])->add(new AuthMiddleware());

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

// Grupo de rutas protegidas
// $app->group('/api', function ($group) {
//     $group->get('/test-auth', function ($request, $response) {
//         $userId = $request->getAttribute('user_id');
//         $response->getBody()->write(json_encode([
//             "status" => "success",
//             "message" => "Acceso concedido para el usuario ID: " . $userId
//         ]));
//         return $response->withHeader('Content-Type', 'application/json');
//     });

//     $group->get('/users/{user_id}', [UserController::class, 'verPerfil']);
//     $group->put('/users/{user_id}', [UserController::class, 'editarPerfil']);
//     $group->get('/users', [UserController::class, 'listarUsuarios']);
//     $group->post('/logout', [UserController::class, 'logout']);

//     $group->get('/portfolio', [PortfolioController::class, 'obtenerPortafolio']);
//     $group->delete('/portfolio/{asset_id}', [PortfolioController::class, 'borrarPortafolio']);

//     $group->post('/trade/buy', [TradeController::class, 'buy']);
//     $group->post('/trade/sell', [TradeController::class, 'vender']);

//     $group->get('/transactions', [TransactionController::class, 'obtenerTransacciones']);
// })->add(new AuthMiddleware());

$app->run();
