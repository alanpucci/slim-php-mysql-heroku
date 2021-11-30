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

    public function crearEncuesta()
    {

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta_comanda = $objAccesoDatos->prepararConsulta("SELECT c.id, e.id as estado FROM comandas c LEFT JOIN estado_comandas e ON c.estado_id = e.id WHERE id_codigo=:comanda_id");
        $consulta_comanda->bindValue(':comanda_id', $this->comanda_id);
        $consulta_comanda->execute();
        $comanda = $consulta_comanda->fetch(PDO::FETCH_ASSOC);
        if(!$comanda && !$comanda["id"]){
            throw new Exception("No existe una comanda con ese id");
        }
        if($comanda["estado"] < 5){
            throw new Exception("Los clientes todavía no terminaron de comer");
        }
        $consulta_encuesta = $objAccesoDatos->prepararConsulta("SELECT * FROM encuestas WHERE comanda_id=:comanda_id");
        $consulta_encuesta->bindValue(':comanda_id', $comanda["id"]);
        $consulta_encuesta->execute();
        $encuesta = $consulta_encuesta->fetch(PDO::FETCH_ASSOC);
        if($encuesta && $encuesta["id"]){
            throw new Exception("Ya existe una encuesta registrada para esa comanda");
        }
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO encuestas (mesa_puntaje, restaurant_puntaje, mozo_puntaje, cocinero_puntaje, descripcion, comanda_id, fecha_creacion) 
                                                        VALUES(:mesa_puntaje, :restaurant_puntaje, :mozo_puntaje, :cocinero_puntaje, :descripcion, :comanda_id, :fecha_creacion)");
        $date = new DateTime("now");
        $fechaCreacion = $date->format('Y-m-d H:i:s');
        $consulta->bindValue(':mesa_puntaje', $this->mesa_puntaje);
        $consulta->bindValue(':restaurant_puntaje', $this->restaurant_puntaje);
        $consulta->bindValue(':mozo_puntaje', $this->mozo_puntaje);
        $consulta->bindValue(':cocinero_puntaje', $this->cocinero_puntaje);
        $consulta->bindValue(':descripcion', $this->descripcion);
        $consulta->bindValue(':comanda_id', $comanda["id"]);
        $consulta->bindValue(':fecha_creacion', $fechaCreacion);
        $consulta->execute();
        $comanda_modificar = $objAccesoDatos->prepararConsulta("UPDATE comandas SET estado_id=:estado WHERE id_codigo=:comanda_id");
        $comanda_modificar->bindValue(':comanda_id', $this->comanda_id);
        $comanda_modificar->bindValue(':estado', 6);
        $comanda_modificar->execute();
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