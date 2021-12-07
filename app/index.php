<?php
// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);
use Illuminate\Support\Facades\Route;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require_once './controllers/EmpleadoController.php';
require_once './controllers/ProductoController.php';
require_once './controllers/PedidoController.php';
require_once './controllers/MesaController.php';
require_once './controllers/EncuestaController.php';
require_once './controllers/ComandaController.php';
require_once './controllers/PDFController.php';
require_once './middlewares/Verificadora.php';
require_once './db/AccesoDatos.php';

require __DIR__ . '/../vendor/autoload.php';

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->setBasePath('/app');
$app->addBodyParsingMiddleware();

date_default_timezone_set('America/Argentina/Buenos_Aires');

$app->addErrorMiddleware(true, true, true);

// peticiones
$app->post('/login',  \EmpleadoController::class . ':Login');

$app->group('/empleados', function (RouteCollectorProxy $group) {
    $group->get('[/]', \EmpleadoController::class . ':TraerTodos')->add(\Verificadora::class . ':ValidarToken');
    $group->post('[/]', \EmpleadoController::class . ':CargarUno')->add(\Verificadora::class . ':ValidarSocio');
    $group->put('/{id}', \EmpleadoController::class . ':ModificarUno')->add(\Verificadora::class . ':ValidarSocio');
  });

$app->post('/fotos', \ComandaController::class . ':SacarFoto')->add(\Verificadora::class . ':ValidarMozo');

$app->post('/cargarCSV', \ComandaController::class . ':CargarCSV')->add(\Verificadora::class . ':ValidarEmpleado');

$app->post('/guardarCSV', \ComandaController::class . ':GuardarCSV')->add(\Verificadora::class . ':ValidarEmpleado');

$app->group('/encuestas', function (RouteCollectorProxy $group) {
  $group->post('[/]', \EncuestaController::class . ':CargarUno');
  $group->get('[/]', \EncuestaController::class . ':TraerMejores')->add(\Verificadora::class . ':ValidarSocio');
});

$app->post('/pdf', \PDFController::class . ':CrearPDF')->add(\Verificadora::class . ':ValidarSocio');

$app->group('/pedidos', function (RouteCollectorProxy $group) {
  $group->get('[/]', \PedidoController::class . ':TraerTodos')->add(\Verificadora::class . ':ValidarEmpleado');
  $group->put('/{id}', \PedidoController::class . ':ModificarUno')->add(\Verificadora::class . ':ValidarEmpleado');
});

$app->group('/productos', function (RouteCollectorProxy $group) {
  $group->get('[/]', \ProductoController::class . ':TraerTodos')->add(\Verificadora::class . ':ValidarEmpleado');
  $group->post('[/]', \ProductoController::class . ':CargarUno')->add(\Verificadora::class . ':ValidarEmpleado');
});

$app->group('/mesas', function (RouteCollectorProxy $group) {
  $group->get('[/]', \MesaController::class . ':TraerTodos')->add(\Verificadora::class . ':ValidarSocio');
  $group->post('[/]', \MesaController::class . ':CargarUno')->add(\Verificadora::class . ':ValidarEmpleado');
  $group->put('/{id}', \MesaController::class . ':CerrarMesa')->add(\Verificadora::class . ':ValidarSocio');
});

$app->get('/mesaUsada', \MesaController::class . ':MesaUsada')->add(\Verificadora::class . ':ValidarSocio');

$app->get('/pedidosConDemora', \PedidoController::class . ':PedidosConDemora')->add(\Verificadora::class . ':ValidarSocio');

$app->get('/pedidosATiempo', \PedidoController::class . ':PedidosATiempo')->add(\Verificadora::class . ':ValidarSocio');

$app->group('/comandas', function (RouteCollectorProxy $group) {
  $group->get('[/]', \ComandaController::class . ':TraerTodos')->add(\Verificadora::class . ':ValidarEmpleado');
  $group->get('/{id}', \ComandaController::class . ':ConsultarDemora');
  $group->post('[/]', \ComandaController::class . ':CargarUno')->add(\Verificadora::class . ':ValidarMozo');
  $group->put('/{id}', \ComandaController::class . ':ModificarUno')->add(\Verificadora::class . ':ValidarMozo');
});

// Run app
$app->run();

