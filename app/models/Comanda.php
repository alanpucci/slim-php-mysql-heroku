<?php
class Comanda
{
    public $id;
    public $fecha_creacion;
    public $mesa;
    public $nombre_cliente;
    public $estado;

    public function crearComanda($mozo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $mesa = $objAccesoDatos->prepararConsulta("SELECT id, estado_id FROM mesas WHERE id_codigo=:mesa_id");
        $mesa->bindValue(':mesa_id', $this->mesa);
        $mesa->execute();
        $mesa_result = $mesa->fetch(PDO::FETCH_ASSOC);
        if($mesa_result && $mesa_result["id"]){
            do{
                $id = substr(bin2hex(random_bytes(3)), 0, -1);
                $comanda = $objAccesoDatos->prepararConsulta("SELECT * FROM comandas WHERE id_codigo=:id");
                $comanda->bindValue(':id', $id);
                $comanda->execute();
                $comanda_result = $comanda->fetch(PDO::FETCH_ASSOC);
            }while($comanda_result);
            $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO comandas (id_codigo, fecha_creacion, mesa_id, nombre_cliente)
                                                        VALUES (:id, :fecha_creacion, :mesa_id, :nombre_cliente)");
            $date = new DateTime("now");
            $fechaCreacion = $date->format('Y-m-d H:i:s');
            $consulta->bindValue(':id', $id);
            $consulta->bindValue(':fecha_creacion', $this->fecha_creacion == null ? $fechaCreacion : $this->fecha_creacion);
            $consulta->bindValue(':mesa_id', $mesa_result["id"]);
            $consulta->bindValue(':nombre_cliente', $this->nombre_cliente);
            $consulta->execute();
            $comanda = $consulta->fetch(PDO::FETCH_ASSOC);
            $comanda_id = $objAccesoDatos->obtenerUltimoId();
            $mesa = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado_id=:estado_id WHERE id=:mesa_id");
            $mesa->bindValue(':mesa_id', $mesa_result["id"]);
            $mesa->bindValue(':estado_id', 1);
            $mesa->execute();
            $sector = $objAccesoDatos->prepararConsulta("SELECT * FROM sectores WHERE nombre=:sector");
            $sector->bindValue(':sector', $mozo->sector);
            $sector->execute();
            $sector_result = $sector->fetch(PDO::FETCH_ASSOC);
            $operacion = $objAccesoDatos->prepararConsulta("INSERT INTO operaciones (empleado_id, comanda_id, sector_id)
                                                            VALUES (:empleado_id, :comanda_id, :sector_id)");
            $operacion->bindValue(':empleado_id', $mozo->id);
            $operacion->bindValue(':comanda_id', $comanda_id);
            $operacion->bindValue(':sector_id', $sector_result["id"]);
            $operacion->execute();
            foreach ($this->pedidos as $key) {
                $producto = $objAccesoDatos->prepararConsulta("SELECT id FROM productos WHERE nombre=:nombre");
                $producto->bindValue(':nombre', $key["nombre"]);
                $producto->execute();
                $producto_result = $producto->fetch(PDO::FETCH_ASSOC);
                $consulta_pedidos = $objAccesoDatos->prepararConsulta("INSERT INTO pedidos (comanda_id, producto_id, cantidad, estado_id)
                                                                        VALUES (:comanda_id, :producto_id, :cantidad,  :estado_id)");
                $consulta_pedidos->bindValue(':comanda_id', $comanda_id);
                $consulta_pedidos->bindValue(':producto_id', $producto_result["id"]);
                $consulta_pedidos->bindValue(':cantidad', $key["cantidad"]);
                $consulta_pedidos->bindValue(':estado_id', 1);
                $consulta_pedidos->execute();
            }
            return $id;
        }
        throw new Exception("Error al crear la comanda");
    }

    public function modificarComanda($cobrar, $usuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT MIN(pe.estado_id) as estado FROM pedidos pe WHERE pe.comanda_id=:id");
        $consulta->bindValue(':id', $this->id);
        $consulta->execute();
        $estado_pedido = $consulta->fetch(PDO::FETCH_ASSOC);
        if(!$cobrar && $estado_pedido["estado"] != 3){
            throw new Exception("Todavía hay pedidos que no están listos, no se puede servir la comida!");
        }
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE pedidos SET estado_id=:estado WHERE comanda_id=:id");
        $consulta->bindValue(':id', $this->id);
        $consulta->bindValue(':estado', $cobrar ? 5 : 4);
        $consulta->execute();
        $mesa = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado_id=:estado_id WHERE id=:mesa_id");
        $mesa->bindValue(':mesa_id', $this->mesa_id);
        $mesa->bindValue(':estado_id', $cobrar ? 3 : 2);
        $mesa->execute();
        $sector = $objAccesoDatos->prepararConsulta("SELECT * FROM sectores WHERE nombre=:sector");
        $sector->bindValue(':sector', $usuario->sector);
        $sector->execute();
        $sector_result = $sector->fetch(PDO::FETCH_ASSOC);
        $operacion = $objAccesoDatos->prepararConsulta("INSERT INTO operaciones (empleado_id, comanda_id, sector_id)
                                                            VALUES (:empleado_id, :comanda_id, :sector_id)");
        $operacion->bindValue(':empleado_id', $usuario->id);
        $operacion->bindValue(':comanda_id',  $this->id);
        $operacion->bindValue(':sector_id', $sector_result["id"]);
        $operacion->execute();
        if(!$cobrar){
            return "La comida fue servida. Buen provecho!";
        }else{
            $consulta_total = $objAccesoDatos->prepararConsulta("SELECT SUM(pe.cantidad*pr.precio) as total FROM comandas c 
                                                                LEFT JOIN pedidos pe ON pe.comanda_id = c.id LEFT JOIN productos pr ON pe.producto_id = pr.id 
                                                                WHERE c.id=:id");
            $consulta_total->bindValue(':id', $this->id);
            $consulta_total->execute();
            $respuesta = $consulta_total->fetch(PDO::FETCH_ASSOC);
            return "Se les cobró $".$respuesta["total"]." a los clientes, gracias por elegirnos!";
        }
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT c.id, c.fecha_creacion,
                                                    m.id_codigo as mesa, c.nombre_cliente, MAX(pe.id) as pedidos, MIN(pe.estado_id) as estado,
                                                    MAX(cast(pe.tiempo_preparacion as DateTime)) AS tiempo_estipulado FROM comandas c 
                                                    LEFT JOIN mesas m ON c.mesa_id = m.id
                                                    LEFT JOIN pedidos pe ON pe.comanda_id = c.id GROUP BY c.id");
        $consulta->execute();
        $comandas = $consulta->fetchAll(PDO::FETCH_CLASS, 'Comanda');
        foreach ($comandas as $key) {
            if($key->pedidos){
                $consulta_pedidos = $objAccesoDatos->prepararConsulta("SELECT pr.nombre as nombre, pe.cantidad as cantidad, pr.tipo as tipo,
                                                                pr.precio as precio, s.nombre as sector, pe.tiempo_preparacion, pe.estado_id as estado
                                                                FROM pedidos pe
                                                                LEFT JOIN productos pr ON pe.producto_id = pr.id 
                                                                LEFT JOIN sectores s ON s.id = pr.sector_id
                                                                WHERE pe.comanda_id=:id");
                $consulta_pedidos->bindValue(':id', $key->id);
                $consulta_pedidos->execute();
                $pedidos = $consulta_pedidos->fetchAll(PDO::FETCH_ASSOC);
                $key->pedidos = $pedidos;
            }
        }
        return $comandas;
    }

    public static function obtenerUno($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comandas WHERE id=:id");
        $consulta->bindValue(':id', $id);
        $consulta->execute();
        $comanda = $consulta->fetchAll(PDO::FETCH_CLASS, "Comanda");
        return $comanda;
    }

    public static function obtenerUnoPorCodigo($codigo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comandas WHERE id_codigo=:id");
        $consulta->bindValue(':id', $codigo);
        $consulta->execute();
        $comanda = $consulta->fetchAll(PDO::FETCH_CLASS, "Comanda");
        return $comanda;
    }

    public static function calcularDemora($comanda, $mesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT MAX(cast(pe.tiempo_preparacion as DateTime)) AS demora FROM pedidos pe 
                                                        LEFT JOIN comandas c ON c.id=pe.comanda_id AND c.id_codigo=:id_codigo 
                                                        GROUP BY pe.comanda_id LIMIT 1");
        $consulta->bindValue(':id_codigo', $comanda->id_codigo);
        $consulta->execute();
        $respuesta = $consulta->fetch(PDO::FETCH_ASSOC);
        $date = new DateTime("now");
        $ahora = $date->format('Y-m-d H:i:s');
        $time = new DateTime($respuesta["demora"]);
        $tiempoPreparacion = $time->format('Y-m-d H:i:s');
        if($ahora>$tiempoPreparacion){
            throw new Exception("Tu pedido debería haberse entregado");
        }
        $dif = $date->diff($time);
        return $dif->format('%i');
    }

    public static function SubirImagen($foto, $idComanda){
        if(!file_exists("ImagenesComandas/")){
            mkdir("ImagenesComandas/",0777,true);
        }
        $nombre = $foto["name"];
        $destino = "ImagenesComandas/".$idComanda.".".$nombre;
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $comanda = $objAccesoDatos->prepararConsulta("SELECT * FROM comandas WHERE id_codigo=:id");
        $comanda->bindValue(':id', $idComanda);
        $comanda->execute();
        $comanda_result = $comanda->fetch(PDO::FETCH_ASSOC);
        if(!$comanda_result){
            throw new Exception("Comanda inexistente");
        }
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO imagenes (nombre,comanda_id) VALUES(:foto, :comanda_id)");
        $consulta->bindValue(':foto', $nombre);
        $consulta->bindValue(':comanda_id', $comanda_result["id"]);
        $consulta->execute();
        if(move_uploaded_file($foto["tmp_name"], $destino)){
            return "Archivo subido con exito";
        }else{
            return "Ha ocurrido un error";
        }
    }
}