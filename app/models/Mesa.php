<?php

class Mesa
{
    public $id;
    public $estado;

    public function crearMesa()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $estado = $objAccesoDatos->prepararConsulta("SELECT id FROM estado_mesas WHERE nombre=:estado");
        $estado->bindValue(':estado', $this->estado);
        $estado->execute();
        $result = $estado->fetch(PDO::FETCH_ASSOC);
        if($result && $result["id"]){
            do{
                $id = substr(bin2hex(random_bytes(3)), 0, -1);
                $mesa = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas WHERE id_codigo=:id");
                $mesa->bindValue(':id', $id);
                $mesa->execute();
                $mesa_result = $mesa->fetch(PDO::FETCH_ASSOC);
            }while($mesa_result);
            $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO mesas (id_codigo, estado_id) VALUES (:id, :estado)");
            $consulta->bindValue(':id', $id);
            $consulta->bindValue(':estado', $result["id"]);
            $consulta->execute();
            return "Mesa creada exitosamente";
        }
        return "Error al crear la mesa";
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT m.id, e.nombre as estado FROM mesas m LEFT JOIN estado_mesas e ON m.estado_id = e.id");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerMasUsada()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT m.id_codigo as mesa, COUNT(c.mesa_id) as cantidad FROM comandas c LEFT JOIN mesas m ON c.mesa_id=m.id GROUP BY c.mesa_id LIMIT 1");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerUno($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT m.id, e.nombre as estado FROM mesas m LEFT JOIN estado_mesas e ON m.estado_id = e.id WHERE m.id_codigo=:id");
        $consulta->bindValue(':id', $id);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public function cerrar()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta_mesa = $objAccesoDatos->prepararConsulta("SELECT m.estado_id as estado FROM mesas m WHERE m.id=:id");
        $consulta_mesa->bindValue(':id', $this->id);
        $consulta_mesa->execute();
        $mesa = $consulta_mesa->fetch(PDO::FETCH_ASSOC);
        if($mesa && $mesa["estado"] != 3){
            throw new Exception("La mesa no puede ser cerrada");
        }
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado_id=:estado WHERE id=:id");
        $consulta->bindValue(':id', $this->id);
        $consulta->bindValue(':estado', 4);
        $consulta->execute();
        return;
    }

    public static function calcularDemora($id){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT c.tiempo_preparacion, c.fecha_creacion, e.id as estado FROM mesas m 
                                                        LEFT JOIN comandas c ON c.mesa_id = m.id 
                                                        LEFT JOIN estado_comandas e ON e.id = c.estado_id
                                                        WHERE c.id_codigo=:id");
        $consulta->bindValue(':id', $id);
        $consulta->execute();
        $respuesta = $consulta->fetch(PDO::FETCH_ASSOC);
        $date = new DateTime("now");
        $ahora = $date->format('Y-m-d H:i:s');
        $time = new DateTime($respuesta["fecha_creacion"]);
        $time->add(new DateInterval('PT' . $respuesta["tiempo_preparacion"] . 'M'));
        $tiempoPreparacion = $time->format('Y-m-d H:i:s');
        if($ahora>$tiempoPreparacion){
            if($respuesta["estado"] == 1 || $respuesta["estado"] == 2){
                return "Tu pedido ya esta listo para servir";
            }
            throw new Exception("Tu pedido ya fue entregado");
        }
        $dif = $date->diff($time);
        return $dif->format('%i');
    }
}