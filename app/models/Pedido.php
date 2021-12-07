<?php

class Pedido
{
    public $id;
    public $producto;
    public $comanda;
    public $cantidad;
    public $estado;
    public $tiempo_preparacion;

    public static function obtenerTodos($sector, $estado)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT pe.id, pr.nombre as nombre, pe.cantidad as cantidad, pr.tipo as tipo, pr.precio as precio,
                                                        s.nombre as sector, e.nombre as estado, pe.tiempo_preparacion
                                                        FROM pedidos pe
                                                        LEFT JOIN productos pr ON pe.producto_id = pr.id 
                                                        LEFT JOIN sectores s ON s.id = pr.sector_id
                                                        LEFT JOIN estado_comandas e ON pe.estado_id = e.id
                                                        WHERE s.nombre=:sector AND e.id=:estado");
        $consulta->bindValue(':sector', $sector);
        $consulta->bindValue(':estado', $estado);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function obtenerUno($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT pe.id, s.nombre as sector, pe.tiempo_preparacion, pe.estado_id as estado FROM pedidos pe 
                                                        LEFT JOIN productos pr ON pe.producto_id = pr.id 
                                                        LEFT JOIN sectores s ON pr.sector_id = s.id 
                                                        WHERE pe.id=:id");
        $consulta->bindValue(':id', $id);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function modificarPedido($pedido, $tiempo_preparacion, $estado)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE pedidos SET estado_id=:estado, tiempo_preparacion=:tiempo_preparacion WHERE id=:id");
        $consulta->bindValue(':id', $pedido["id"]);
        $consulta->bindValue(':estado', $estado);
        $consulta->bindValue(':tiempo_preparacion', $tiempo_preparacion);
        $consulta->execute();
    }

    public static function obtenerDemorados()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $date = new DateTime("now");
        $tiempoAhora = $date->format('Y-m-d-H_i_s');
        $consulta = $objAccesoDatos->prepararConsulta("SELECT pe.id, pr.nombre, pe.cantidad, pr.precio, pr.tipo, pe.tiempo_preparacion, c.id_codigo as comanda, m.id_codigo as mesa,
                                                        TIMEDIFF(:tiempo1, pe.tiempo_preparacion) as demorado, e.nombre as estado FROM pedidos pe 
                                                        LEFT JOIN productos pr ON pr.id=pe.producto_id
                                                        LEFT JOIN comandas c ON c.id=pe.comanda_id
                                                        LEFT JOIN mesas m ON c.mesa_id=m.id
                                                        LEFT JOIN estado_comandas e ON pe.estado_id=e.id
                                                        WHERE TIMEDIFF(:tiempo2, pe.tiempo_preparacion)>0 && pe.estado_id < 4");
        $consulta->bindValue(':tiempo1', $tiempoAhora);
        $consulta->bindValue(':tiempo2', $tiempoAhora);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerEntregados()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $date = new DateTime("now");
        $tiempoAhora = $date->format('Y-m-d-H_i_s');
        $consulta = $objAccesoDatos->prepararConsulta("SELECT pe.id, pr.nombre, pe.cantidad, pr.precio, pr.tipo, pe.tiempo_preparacion, c.id_codigo as comanda, m.id_codigo as mesa,
                                                        e.nombre as estado FROM pedidos pe 
                                                        LEFT JOIN productos pr ON pr.id=pe.producto_id
                                                        LEFT JOIN comandas c ON c.id=pe.comanda_id
                                                        LEFT JOIN mesas m ON c.mesa_id=m.id
                                                        LEFT JOIN estado_comandas e ON pe.estado_id=e.id
                                                        WHERE TIMEDIFF(:tiempo, pe.tiempo_preparacion)>0 && pe.estado_id > 3");
        $consulta->bindValue(':tiempo', $tiempoAhora);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}