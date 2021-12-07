<?php
require_once './models/Pedido.php';
require_once './middlewares/AutentificadorJWT.php';

class PedidoController extends Pedido
{
    public function TraerTodos($request, $response, $args)
    {
      try {
        $parametros = $request->getQueryParams();
        if(!isset($parametros["estado"])){
          $parametros["estado"] = "Pendiente";
        }
        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);
        $usuario = AutentificadorJWT::ObtenerData($token);
        $lista = Pedido::obtenerTodos($usuario->sector, $parametros["estado"]);
        $payload = json_encode(array("lista pedidos" => $lista));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }

    public function ModificarUno($request, $response, $args)
    {
      try {
        $pedido = Pedido::obtenerUno($args["id"])[0];
        $parametros =  $request->getParsedBody();
        if($pedido){
          $header = $request->getHeaderLine('Authorization');
            $token = trim(explode("Bearer", $header)[1]);
            $data = AutentificadorJWT::ObtenerData($token);
            if($pedido["sector"] != $data->sector){
              throw new Exception("Empleado no autorizado a modificar ese pedido");
            }
            if(isset($parametros["tiempo_preparacion"])){
              $date = new DateTime("now");
              $date->add(new DateInterval('PT' . $parametros["tiempo_preparacion"] . 'M'));
              $tiempo_preparacion = $date->format('Y-m-d H:i:s');
              $estado = 2;
            }else if(isset($parametros["estado"]) && $parametros["estado"]>2){
              if($pedido["estado"] < 2){
                throw new Exception("No se puede modificar. El pedido ni comenzó su preparación");
              }
              $estado = $parametros["estado"];
              $tiempo_preparacion = $pedido["tiempo_preparacion"];
              $date = new DateTime("now");
              $ahora = $date->format('Y-m-d H:i:s');
              $time = new DateTime($tiempo_preparacion);
              $tiempoPreparacion = $time->format('Y-m-d H:i:s');
              if($ahora<$tiempoPreparacion){
                  throw new Exception("El pedido todavía no esta listo para servir");
              }
            }else{
              throw new Exception("Parametros inválidos");
            }
            Pedido::modificarPedido($pedido, $tiempo_preparacion, $estado);
            $payload = json_encode(array("mensaje"=>"Pedido modificado exitosamente"));
            $response->getBody()->write($payload);
            return $response
              ->withHeader('Content-Type', 'application/json');
        }else{
          throw new Exception("No se encontró un pedido con ese ID");
        }
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }

    public function PedidosConDemora($request, $response, $args)
    {
      try {
        $lista = Pedido::obtenerDemorados();
        $payload = json_encode(array("lista pedidos con demora" => $lista));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }

    public function PedidosATiempo($request, $response, $args)
    {
      try {
        $lista = Pedido::obtenerEntregados();
        $payload = json_encode(array("lista pedidos entregados a tiempo" => $lista));
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