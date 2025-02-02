<?php
require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';

class MesaController extends Mesa implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
      try {
        $parametros = $request->getParsedBody();
        if(!isset($parametros["estado"])){
          throw new Exception("Parametros invalidos");
        }
        $mesa = new Mesa();
        $mesa->estado = $parametros["estado"];
        $respuesta = $mesa->crearMesa();
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
        $lista = Mesa::obtenerTodos();
        $payload = json_encode(array("listaMesas" => $lista));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }

    public function TraerTodosPorParam($request, $response, $args)
    {
      try {
        $parametros = $request->getQueryParams();
        if(isset($parametros["masUsada"])){
          $lista = Mesa::obtenerMasUsada();
        }
        if(isset($parametros["menosUsada"])){
          $lista = Mesa::obtenerMenosUsada();
        }
        if(isset($parametros["mayorFacturaTotal"])){
          $lista = Mesa::obtenerMayorFacturaTotal();
        }
        if(isset($parametros["menorFacturaTotal"])){
          $lista = Mesa::obtenerMenorFacturaTotal();
        }
        if(isset($parametros["mayorFactura"])){
          $lista = Mesa::obtenerMayorFactura();
        }
        if(isset($parametros["menorFactura"])){
          $lista = Mesa::obtenerMenorFactura();
        }
        if(isset($parametros["desde"]) && isset($parametros["hasta"])){
          $lista = Mesa::obtenerTotalEntreFechas($parametros["desde"], $parametros["hasta"]);
        }
        if(isset($parametros["mejoresComentarios"])){
          $lista = Mesa::obtenerMayorPuntaje();
        }
        if(isset($parametros["peoresComentarios"])){
          $lista = Mesa::obtenerMenorPuntaje();
        }
        $payload = json_encode(array("listaMesas" => $lista));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }

    public function MesaUsada($request, $response, $args)
    {
      try {
        $respuesta = Mesa::obtenerMasUsada();
        $payload = json_encode(array("mensaje" => "La mesa mas usada es: ".$respuesta["mesa"].", ".$respuesta["cantidad"]." veces"));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }

    public function CerrarMesa($request, $response, $args)
  {
    try {
      $mesa = Mesa::obtenerUno($args["id"])[0];
      if ($mesa) {
        $mesa->cerrar();
        $payload = json_encode(array("mensaje" => "Mesa cerrada"));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } else {
        throw new Exception("No se encontró una mesa con ese id");
      }
    } catch (\Throwable $th) {
      $response->getBody()->write(json_encode(array("mensaje" => "ERROR, " . $th->getMessage())));
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
  }
}