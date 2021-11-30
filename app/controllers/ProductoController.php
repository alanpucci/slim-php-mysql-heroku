<?php
require_once './models/Producto.php';
require_once './interfaces/IApiUsable.php';

class ProductoController extends Producto implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
      try {
        $parametros = $request->getParsedBody();
        if(!isset($parametros["precio"]) || !isset($parametros["nombre"]) || !isset($parametros["tipo"])){
          throw new Exception("Parametros invalidos");
        }
        $producto = new Producto();
        $producto->precio = $parametros["precio"];
        $producto->nombre = $parametros["nombre"];
        $producto->tipo = $parametros["tipo"];
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
        $lista = Producto::obtenerTodos();
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