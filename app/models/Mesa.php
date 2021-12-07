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
        $consulta = $objAccesoDatos->prepararConsulta("SELECT m.id, m.id_codigo, e.nombre as estado FROM mesas m LEFT JOIN estado_mesas e ON m.estado_id = e.id");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerTodosPorParam()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT m.id, e.nombre as estado FROM mesas m LEFT JOIN estado_mesas e ON m.estado_id = e.id");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerMasUsada()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT m.id_codigo as mesa, COUNT(c.mesa_id) as cantidad FROM comandas c LEFT JOIN mesas m ON c.mesa_id=m.id GROUP BY c.mesa_id ORDER BY cantidad DESC LIMIT 1");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerMayorPuntaje()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT MAX(e.mesa_puntaje) as puntaje, m.id_codigo as mesa, e.descripcion FROM encuestas e 
                                                        LEFT JOIN mesas m ON e.mesa_id=m.id 
                                                        GROUP BY e.mesa_id, e.descripcion ORDER BY puntaje DESC LIMIT 1");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerMenorPuntaje()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT MIN(e.mesa_puntaje) as puntaje, m.id_codigo as mesa, e.descripcion FROM encuestas e 
                                                        LEFT JOIN mesas m ON e.mesa_id=m.id 
                                                        GROUP BY e.mesa_id, e.descripcion ORDER BY puntaje ASC LIMIT 1");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerTotalEntreFechas($desde, $hasta)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT SUM(pe.cantidad*pr.precio) as total FROM pedidos pe
                                                        LEFT JOIN productos pr ON pe.producto_id=pr.id
                                                        LEFT JOIN comandas c ON pe.comanda_id=c.id
                                                        WHERE c.fecha_creacion BETWEEN :desde AND :hasta");
        $consulta->bindValue(':desde', $desde);
        $consulta->bindValue(':hasta', $hasta);
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerMayorFacturaTotal()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT SUM(pe.cantidad*pr.precio) as total, m.id_codigo as mesa FROM pedidos pe
                                                        LEFT JOIN productos pr ON pe.producto_id=pr.id
                                                        LEFT JOIN comandas c ON pe.comanda_id=c.id
                                                        LEFT JOIN mesas m ON c.mesa_id=m.id
                                                        GROUP BY c.mesa_id
                                                        ORDER BY total DESC
                                                        limit 1");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerMayorFactura()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT SUM(pe.cantidad*pr.precio) as total, m.id_codigo as mesa FROM pedidos pe
                                                        LEFT JOIN productos pr ON pe.producto_id=pr.id
                                                        LEFT JOIN comandas c ON pe.comanda_id=c.id
                                                        LEFT JOIN mesas m ON c.mesa_id=m.id
                                                        GROUP BY c.id
                                                        ORDER BY total DESC");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerMenorFacturaTotal()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT SUM(pe.cantidad*pr.precio) as total, m.id_codigo as mesa FROM pedidos pe
                                                        LEFT JOIN productos pr ON pe.producto_id=pr.id
                                                        LEFT JOIN comandas c ON pe.comanda_id=c.id
                                                        LEFT JOIN mesas m ON c.mesa_id=m.id
                                                        GROUP BY c.mesa_id
                                                        ORDER BY total ASC
                                                        limit 1");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerMenorFactura()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT SUM(pe.cantidad*pr.precio) as total, m.id_codigo as mesa FROM pedidos pe
                                                        LEFT JOIN productos pr ON pe.producto_id=pr.id
                                                        LEFT JOIN comandas c ON pe.comanda_id=c.id
                                                        LEFT JOIN mesas m ON c.mesa_id=m.id
                                                        GROUP BY c.id
                                                        ORDER BY total ASC");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerMenosUsada()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT COUNT(id_codigo) as cantidad , id_codigo as mesa FROM comandas
                                                        GROUP BY id_codigo
                                                        ORDER BY cantidad asc
                                                        limit 1");
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
}