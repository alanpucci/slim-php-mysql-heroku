<?php

class Encuesta
{
    public $id;
    public $mesa_puntaje;
    public $restaurant_puntaje;
    public $mozo_puntaje;
    public $cocinero_puntaje;
    public $descripcion;
    public $comanda_id;
    public $mesa_id;

    public function crearEncuesta()
    {
        $comanda = Comanda::obtenerUnoPorCodigo($this->comanda_id);
        if(!$comanda){
          throw new Exception("No se encontró una comanda con ese codigo");
        }
        $mesa = Mesa::obtenerUno($this->mesa_id);
        if(!$mesa){
          throw new Exception("No se encontró una mesa con ese codigo");
        }
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta_encuesta = $objAccesoDatos->prepararConsulta("SELECT * FROM encuestas WHERE comanda_id=:comanda_id");
        $consulta_encuesta->bindValue(':comanda_id', $comanda[0]->id);
        $consulta_encuesta->execute();
        $encuesta = $consulta_encuesta->fetch(PDO::FETCH_ASSOC);
        if($encuesta && $encuesta["id"]){
            throw new Exception("Ya existe una encuesta registrada para esa comanda");
        }
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO encuestas (mesa_puntaje, restaurant_puntaje, mozo_puntaje, cocinero_puntaje, descripcion, comanda_id, mesa_id, fecha_creacion) 
                                                        VALUES(:mesa_puntaje, :restaurant_puntaje, :mozo_puntaje, :cocinero_puntaje, :descripcion, :comanda_id, :mesa_id, :fecha_creacion)");
        $date = new DateTime("now");
        $fechaCreacion = $date->format('Y-m-d H:i:s');
        $consulta->bindValue(':mesa_puntaje', $this->mesa_puntaje);
        $consulta->bindValue(':restaurant_puntaje', $this->restaurant_puntaje);
        $consulta->bindValue(':mozo_puntaje', $this->mozo_puntaje);
        $consulta->bindValue(':cocinero_puntaje', $this->cocinero_puntaje);
        $consulta->bindValue(':descripcion', $this->descripcion);
        $consulta->bindValue(':comanda_id', $comanda[0]->id);
        $consulta->bindValue(':mesa_id', $mesa[0]->id);
        $consulta->bindValue(':fecha_creacion', $fechaCreacion);
        $consulta->execute();
        return "Encuesta registrada exitosamente";
    }

    public static function obtenerMejores()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM encuestas WHERE (mozo_puntaje+restaurant_puntaje+mesa_puntaje+cocinero_puntaje)/4 >= 7");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}