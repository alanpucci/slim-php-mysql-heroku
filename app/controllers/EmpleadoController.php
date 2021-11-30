<?php
require_once './models/Empleado.php';
require_once './interfaces/IApiUsable.php';
require_once './middlewares/AutentificadorJWT.php';

class EmpleadoController extends Empleado implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
      try {
        $parametros = $request->getParsedBody();
        if(!isset($parametros["nombre"]) || !isset($parametros["puesto"]) || !isset($parametros["sector"]) || !isset($parametros["usuario"]) || !isset($parametros["clave"])){
          throw new Exception("Parametros invalidos");
        }
        $empleado = new Empleado();
        $empleado->nombre = $parametros["nombre"];
        $empleado->puesto = $parametros["puesto"];
        $empleado->sector = $parametros["sector"];
        $empleado->usuario = $parametros["usuario"];
        $empleado->clave = $parametros["clave"];
        $respuesta = $empleado->crearEmpleado();
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
        $lista = Empleado::obtenerTodos();
        $payload = json_encode(array("listaEmpleados" => $lista));
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
      $empleado = Empleado::obtenerUno($args["id"])[0];
      $parametros =  $request->getParsedBody();
      if ($empleado) {
        if(!isset($parametros["estado"])){
          throw new Exception("Parametros invalidos");
        }
        $empleado->estado = $parametros["estado"];
        $empleado->modificarEmpleado();
        $payload = json_encode(array("mensaje" => "Empleado modificado exitosamente"));
        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
      } else {
        throw new Exception("No se encontró un empleado con ese id");
      }
    } catch (\Throwable $th) {
      $response->getBody()->write(json_encode(array("mensaje" => "ERROR, " . $th->getMessage())));
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
  }

    public function Login($request, $response, $args)
    {
      try {
        $parametros = $request->getParsedBody();
        if(!isset($parametros["usuario"]) || !isset($parametros["clave"])){
          throw new Exception("Parametros invalidos");
        }
        $empleado = new Empleado();
        $empleado->usuario = $parametros["usuario"];
        $empleado->clave = $parametros["clave"];
        $respuesta = $empleado->validarUsuario();
        if($respuesta){
          $payload = json_encode(array("mensaje" => "Inicio de sesión exitoso", "token" => AutentificadorJWT::CrearToken($respuesta), "puesto" => $respuesta));
          $response->getBody()->write($payload);
          return $response
            ->withHeader('Content-Type', 'application/json');
        }
      } catch (\Throwable $th) {
        $response->getBody()->write(json_encode(array("mensaje" => "ERROR, ".$th->getMessage())));
        return $response
        ->withHeader('Content-Type', 'application/json');
      }
    }
}