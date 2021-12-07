<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Slim\Handlers\Strategies\RequestHandler;
require_once './middlewares/AutentificadorJWT.php';

class Verificadora{
    public function ValidarToken($request, $handler){
        try {
            $header = $request->getHeaderLine('Authorization');
            $token = trim(explode("Bearer", $header)[1]);
            AutentificadorJWT::VerificarToken($token);
            return $handler->handle($request);
        } catch (\Throwable $th) {
            $response = new Response();
            $payload = json_encode(array("mensaje" => "ERROR, ".$th->getMessage(), "status" => 401));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');;
        }
    }

    public function ValidarMozo($request, $handler){
        try {
            $header = $request->getHeaderLine('Authorization');
            if(!empty($header)){
                $token = trim(explode("Bearer", $header)[1]);
                $data = AutentificadorJWT::ObtenerData($token);
                if($data->puesto == "mozo"){
                    return $handler->handle($request);
                }
                throw new Exception("Usuario no autorizado");
            }else{
                throw new Exception("Token vacío");
            }
        } catch (\Throwable $th) {
            $response = new Response();
            $payload = json_encode(array("mensaje" => "ERROR, ".$th->getMessage()));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');;
        }
    }

    public function ValidarSocio($request, $handler){
        try {
            $header = $request->getHeaderLine('Authorization');
            if(!empty($header)){
                $token = trim(explode("Bearer", $header)[1]);
                $data = AutentificadorJWT::ObtenerData($token);
                if($data->puesto == "socio"){
                    return $handler->handle($request);
                }
                throw new Exception("Usuario no autorizado");
            }else{
                throw new Exception("Token vacío");
            }
        } catch (\Throwable $th) {
            $response = new Response();
            $payload = json_encode(array("mensaje" => "ERROR, ".$th->getMessage()));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');;
        }
    }

    public function ValidarEmpleado($request, $handler){
        try {
            $header = $request->getHeaderLine('Authorization');
            if(!empty($header)){
                $token = trim(explode("Bearer", $header)[1]);
                $data = AutentificadorJWT::ObtenerData($token);
                if($data->puesto == "socio" || $data->puesto == "cervecero" || $data->puesto == "mozo" || $data->puesto == "cocinero" || $data->puesto == "bartender"){
                    return $handler->handle($request);
                }
                throw new Exception("Usuario no autorizado");
            }else{
                throw new Exception("Token vacío");
            }
        } catch (\Throwable $th) {
            $response = new Response();
            $payload = json_encode(array("mensaje" => "ERROR, ".$th->getMessage()));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');;
        }
    }
}

?>