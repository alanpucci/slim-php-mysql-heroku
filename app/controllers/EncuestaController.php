<?php
require_once './models/Encuesta.php';
require_once './models/Comanda.php';
require_once './models/Mesa.php';

class EncuestaController extends Encuesta
{
    public function CargarUno($request, $response, $args)
    {
      try {
        $parametros = $request->getParsedBody();
        if(!isset($parametros["mesa_puntaje"]) || !isset($parametros["restaurant_puntaje"]) || !isset($parametros["mozo_puntaje"]) ||
           !isset($parametros["cocinero_puntaje"]) || !isset($parametros["descripcion"]) || !isset($parametros["comanda_id"]) || !isset($parametros["mesa_id"]) ||
           !($parametros["mesa_puntaje"] >=0 && $parametros["mesa_puntaje"] <=10) || !($parametros["restaurant_puntaje"] >=0 && $parametros["restaurant_puntaje"] <=10) ||
           !($parametros["mozo_puntaje"] >=0 && $parametros["mozo_puntaje"] <=10) || !($parametros["cocinero_puntaje"] >=0 && $parametros["cocinero_puntaje"] <=10)){
               throw new Exception("Parametros invalidos");
           }
        $encuesta = new Encuesta();
        $encuesta->mesa_puntaje = $parametros["mesa_puntaje"];
        $encuesta->restaurant_puntaje = $parametros["restaurant_puntaje"];
        $encuesta->mozo_puntaje = $parametros["mozo_puntaje"];
        $encuesta->cocinero_puntaje = $parametros["cocinero_puntaje"];
        $encuesta->descripcion = $parametros["descripcion"];
        $encuesta->comanda_id = $parametros["comanda_id"];
        $encuesta->mesa_id = $parametros["mesa_id"];
        $respuesta = $encuesta->crearEncuesta();
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

    public function TraerMejores($request, $response, $args)
  {
    try {
      $lista = Encuesta::obtenerMejores();
      $payload = json_encode(array("listaComandas" => $lista));
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    } catch (\Throwable $th) {
      $response->getBody()->write(json_encode(array("mensaje" => "ERROR, " . $th->getMessage())));
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
  }
}