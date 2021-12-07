<?php
require_once './models/Producto.php';
require_once './middlewares/AutentificadorJWT.php';
require_once './interfaces/IApiUsable.php';

class ProductoController extends Producto implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
      try {
        $parametros = $request->getParsedBody();
        if(!isset($parametros["precio"]) || !isset($parametros["nombre"]) || !isset($parametros["tipo"]) || !isset($parametros["sector"])){
          throw new Exception("Parametros invalidos");
        }
        $producto = new Producto();
        $producto->precio = $parametros["precio"];
        $producto->nombre = $parametros["nombre"];
        $producto->tipo = $parametros["tipo"];
        $producto->sector = $parametros["sector"];
        $respuesta = $producto->crearProducto();
        $payload = json_encode(array("mensaje" => $respuesta));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }

    public function TraerTodos($request, $response, $args)
    {
      try {
        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);
        $usuario = AutentificadorJWT::ObtenerData($token);
        $lista = Producto::obtenerTodos($usuario->sector);
        $payload = json_encode(array("listaProductos" => $lista));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }
}