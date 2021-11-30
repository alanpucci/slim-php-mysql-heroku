<?php
require_once './models/Encuesta.php';

class EncuestaController extends Encuesta
{
    public function CargarUno($request, $response, $args)
    {
      try {
        $parametros = $request->getParsedBody();
        if(!isset($parametros["mesa_puntaje"]) || !isset($parametros["restaurant_puntaje"]) || !isset($parametros["mozo_puntaje"]) ||
           !isset($parametros["cocinero_puntaje"]) || !isset($parametros["descripcion"]) || !isset($parametros["comanda_id"])){
               throw new Exception("Parametros invalidos");
           }
        $encuesta = new Encuesta();
        $encuesta->mesa_puntaje = $parametros["mesa_puntaje"];
        $encuesta->restaurant_puntaje = $parametros["restaurant_puntaje"];
        $encuesta->mozo_puntaje = $parametros["mozo_puntaje"];
        $encuesta->cocinero_puntaje = $parametros["cocinero_puntaje"];
        $encuesta->descripcion = $parametros["descripcion"];
        $encuesta->comanda_id = $parametros["comanda_id"];
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