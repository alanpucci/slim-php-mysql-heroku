<?php
require_once './models/Comanda.php';
require_once './middlewares/AutentificadorJWT.php';
require_once './interfaces/IApiUsable.php';

class ComandaController extends Comanda implements IApiUsable
{
  public function CargarUno($request, $response, $args)
  {
    try {
      $header = $request->getHeaderLine('Authorization');
      $token = trim(explode("Bearer", $header)[1]);
      $usuario = AutentificadorJWT::ObtenerData($token);
      $parametros =  json_decode($request->getBody(), true);
      if(!isset($parametros["mesa"]) || !isset($parametros["nombre_cliente"]) || !isset($parametros["pedidos"])){
        throw new Exception("Parametros invalidos");
      }
      $comanda = new Comanda();
      $comanda->mesa = $parametros["mesa"];
      $comanda->nombre_cliente = $parametros["nombre_cliente"];
      $comanda->pedidos = $parametros["pedidos"];
      $respuesta = $comanda->crearComanda($usuario);
      $payload = json_encode(array("mensaje" => "Comanda creada exitosamente", "id" => $respuesta));
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    } catch (\Throwable $th) {
      $response->getBody()->write(json_encode(array("mensaje" => "ERROR, " . $th->getMessage())));
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
  }

  public function TraerTodos($request, $response, $args)
  {
    try {
      $lista = Comanda::obtenerTodos();
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

  public function ModificarUno($request, $response, $args)
  {
    try {
      $comanda = Comanda::obtenerUno($args["id"])[0];
      if ($comanda) {
        $header = $request->getHeaderLine('Authorization');
        $token = trim(explode("Bearer", $header)[1]);
        $usuario = AutentificadorJWT::ObtenerData($token);
        $parametros =  $request->getParsedBody();
        $cobrar=false;
        if(isset($parametros["cobrar"])){
          $cobrar=true;
        }
        $respuesta = $comanda->modificarComanda($cobrar,$usuario);
        $payload = json_encode(array("mensaje" => $respuesta));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } else {
        throw new Exception("No se encontró una comanda con ese id");
      }
    } catch (\Throwable $th) {
      $response->getBody()->write(json_encode(array("mensaje" => "ERROR, " . $th->getMessage())));
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
  }

  public function ConsultarDemora($request, $response, $args){
    try {
      $comanda = Comanda::obtenerUnoPorCodigo($args["id"])[0];
      if($comanda){
        $parametros = $request->getQueryParams();
        if(!isset($parametros["mesa_id"])){
          throw new Exception("Se requiere el id de la mesa");
        }
        $demora = Comanda::calcularDemora($comanda, $parametros["mesa_id"]);
        $payload = json_encode(array("mensaje" => "El tiempo de demora de tu pedido es: ".$demora." minutos"));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      }else{
        throw new Exception("No existe una comanda con ese codigo");
      }
    } catch (\Throwable $th) {
      $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
      return $response
      ->withHeader('Content-Type', 'application/json');
    }
  }

  public function SacarFoto($request, $response, $args)
  {
    try {
      $parametros = $request->getParsedBody();
      if(!isset($_FILES["archivo"])){
        throw new Exception("No se detectó ninguna imagen");
      }
      $respuesta = Comanda::SubirImagen($_FILES["archivo"], $parametros["comanda_id"]);
      $payload =  json_encode(array("mensaje" => $respuesta));
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    } catch (\Throwable $th) {
      $response->getBody()->write(json_encode(array("mensaje" => "ERROR, " . $th->getMessage())));
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
  }

  public function CargarCSV($request, $response, $args)
  {
    try {
      if(!isset($_FILES["archivo"])){
        throw new Exception("Debe enviar un archivo CSV con el nombre 'archivo'");
      }
      $fp = file_get_contents($_FILES["archivo"]["tmp_name"]);
      $f = fopen('csvdescarte.csv', 'w');
      fwrite($f,$fp);
      fclose($f);
      $fp = fopen('csvdescarte.csv', 'r');
      $key = fgetcsv($fp, "1024", ",");
      $json = array();
      $comandas = array();
      while ($row = fgetcsv($fp, "1024", ",")) {
        $json[] = array_combine($key, $row);
        $json = $json[0];
        $comanda = new Comanda();
        $comanda->mesa = $json["mesa"];
        $comanda->fecha_creacion = $json["fecha_creacion"];
        $comanda->nombre_cliente = $json["nombre_cliente"];
        $comanda->estado = $json["estado"];
        $pedidos = array();
        for ($i = 0; $i < 99; $i++) {
          if (empty($json["pedidos" . $i . "nombre"])) {
            break;
          }
          array_push($pedidos, ["nombre" => $json["pedidos" . $i . "nombre"], "cantidad" => $json["pedidos" . $i . "cantidad"]]);
        }
        $comanda->pedidos = $pedidos;
        $empleado = new Empleado();
        $empleado->id = 1;
        $empleado->sector = "cocina";
        $respuesta = $comanda->crearComanda($empleado);
        if ($respuesta) {
          $comanda->id = $respuesta;
          array_push($comandas, $comanda);
        }
      }
      fclose($fp);
      $payload = json_encode(array("Comandas cargadas exitosamente:" => $comandas));
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    } catch (\Throwable $th) {
      $response->getBody()->write(json_encode(array("mensaje" => "ERROR, " . $th->getMessage())));
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
  }

  public function GuardarCSV($request, $response, $args)
  {
    try {
      if(!file_exists("CSV/")){
          mkdir("CSV/",0777,true);
      }
      $date = new DateTime("now");
      $tiempoAhora = $date->format('Y-m-d-H_i_s');
      $f = fopen('CSV/comandas_'.$tiempoAhora.'.csv', 'w');
        $data = Comanda::obtenerTodos();
        if(count($data)>0){
          fwrite($f, "id,fecha_creacion,mesa,nombre_cliente,estado,");
          for ($i=0; $i < 10; $i++) { 
            fwrite($f,"pedidos".$i."nombre,pedidos".$i."cantidad,pedidos".$i."tipo,pedidos".$i."precio");
          }
          fwrite($f, "\n");
          foreach ($data as $row){
            $return = array();
            array_walk_recursive($row, function($a) use (&$return) { $return[] = $a; });
            $row = $return;
              fputcsv($f, (array) $row);
          }
          fclose($f);
        }
        $payload = json_encode(array("Comandas guardadas exitosamente:" => $data));
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
