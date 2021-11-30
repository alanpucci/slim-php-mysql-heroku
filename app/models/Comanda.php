<?php
class Comanda
{
    public $id;
    public $fecha_creacion;
    public $mesa;
    public $nombre_cliente;
    public $estado;
    public $tiempo_preparacion;

    public function crearComanda()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $estado = $objAccesoDatos->prepararConsulta("SELECT id FROM estado_comandas WHERE nombre=:estado");
        $estado->bindValue(':estado', $this->fecha_creacion == null ? "Pendiente" : $this->estado);
        $estado->execute();
        $estado_result = $estado->fetch(PDO::FETCH_ASSOC);
        $mesa = $objAccesoDatos->prepararConsulta("SELECT id, estado_id FROM mesas WHERE id_codigo=:mesa_id");
        $mesa->bindValue(':mesa_id', $this->mesa);
        $mesa->execute();
        $mesa_result = $mesa->fetch(PDO::FETCH_ASSOC);
        if($estado_result && $estado_result["id"] && $mesa_result && $mesa_result["id"]){
            do{
                $id = substr(bin2hex(random_bytes(3)), 0, -1);
                $comanda = $objAccesoDatos->prepararConsulta("SELECT * FROM comandas WHERE id_codigo=:id");
                $comanda->bindValue(':id', $id);
                $comanda->execute();
                $comanda_result = $comanda->fetch(PDO::FETCH_ASSOC);
            }while($comanda_result);
            $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO comandas (id_codigo, fecha_creacion, mesa_id, nombre_cliente, estado_id, tiempo_preparacion)
                                                        VALUES (:id, :fecha_creacion, :mesa_id, :nombre_cliente, :estado_id, :tiempo_preparacion)");
            $date = new DateTime("now");
            $fechaCreacion = $date->format('Y-m-d H:i:s');
            $consulta->bindValue(':id', $id);
            $consulta->bindValue(':fecha_creacion', $this->fecha_creacion == null ? $fechaCreacion : $this->fecha_creacion);
            $consulta->bindValue(':mesa_id', $mesa_result["id"]);
            $consulta->bindValue(':nombre_cliente', $this->nombre_cliente);
            $consulta->bindValue(':estado_id', $estado_result["id"]);
            $consulta->bindValue(':tiempo_preparacion', $this->tiempo_preparacion);
            $consulta->execute();
            $comanda = $consulta->fetch(PDO::FETCH_ASSOC);
            $comanda_id = $objAccesoDatos->obtenerUltimoId();
            $mesa = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado_id=:estado_id WHERE id=:mesa_id");
            $mesa->bindValue(':mesa_id', $mesa_result["id"]);
            $mesa->bindValue(':estado_id', 1);
            $mesa->execute();
            foreach ($this->pedidos as $key) {
                $producto = $objAccesoDatos->prepararConsulta("SELECT id FROM productos WHERE nombre=:nombre");
                $producto->bindValue(':nombre', $key["nombre"]);
                $producto->execute();
                $producto_result = $producto->fetch(PDO::FETCH_ASSOC);
                $consulta_pedidos = $objAccesoDatos->prepararConsulta("INSERT INTO pedidos (comanda_id, producto_id, cantidad)
                                                                        VALUES (:comanda_id, :producto_id, :cantidad)");
                $consulta_pedidos->bindValue(':comanda_id', $comanda_id);
                $consulta_pedidos->bindValue(':producto_id', $producto_result["id"]);
                $consulta_pedidos->bindValue(':cantidad', $key["cantidad"]);
                $consulta_pedidos->execute();
            }
            return $id;
        }
        throw new Exception("Error al crear la comanda");
    }

    public function modificarComanda()
    {
        $date = new DateTime("now");
        $ahora = $date->format('Y-m-d H:i:s');
        $time = new DateTime($this->fecha_creacion);
        $time->add(new DateInterval('PT' . $this->tiempo_preparacion . 'M'));
        $tiempoPreparacion = $time->format('Y-m-d H:i:s');
        if($ahora<$tiempoPreparacion){
            throw new Exception("El pedido todavía no esta listo para cambiar su estado");
        }
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $estado = $objAccesoDatos->prepararConsulta("SELECT id, nombre FROM estado_comandas WHERE nombre=:estado");
        $estado->bindValue(':estado', $this->estado);
        $estado->execute();
        $estado_result = $estado->fetch(PDO::FETCH_ASSOC);
        if($estado_result && $estado_result["id"]){
            $consulta = $objAccesoDatos->prepararConsulta("UPDATE comandas SET estado_id=:estado WHERE id=:id");
            $consulta->bindValue(':id', $this->id);
            $consulta->bindValue(':estado', $estado_result["id"]);
            $consulta->execute();
            if($estado_result["nombre"] == "Servido" || $estado_result["nombre"] == "Terminado"){
                $mesa = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado_id=:estado_id WHERE id_codigo=:mesa_id");
                $mesa->bindValue(':mesa_id', $this->mesa_id);
                $mesa->bindValue(':estado_id', $estado_result["nombre"] == "Servido" ? 2 : 3);
                $mesa->execute();
            }
            return $this->id;
        }
        throw new Exception("Error al modificar el estado de la comanda");
    }

    public function modificarComandas()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE comandas SET estado_id=:estado
                                                        WHERE TIMEDIFF(now(), date_add(fecha_creacion,interval tiempo_preparacion minute))>0 && estado_id < 3");
        $consulta->bindValue(':estado', 3);
        $consulta->execute();
        return;
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT c.id, c.fecha_creacion,
                                                     m.id_codigo as mesa, c.nombre_cliente, MAX(pe.id) as pedidos,
                                                  e.nombre as estado, c.tiempo_preparacion FROM comandas c 
                                                  LEFT JOIN estado_comandas e ON c.estado_id = e.id 
                                                  LEFT JOIN mesas m ON c.mesa_id = m.id
                                                  LEFT JOIN pedidos pe ON pe.comanda_id = c.id GROUP BY c.id");
        $consulta->execute();
        $comandas = $consulta->fetchAll(PDO::FETCH_CLASS, 'Comanda');
        foreach ($comandas as $key) {
            if($key->pedidos){
                $consulta_pedidos = $objAccesoDatos->prepararConsulta("SELECT pr.nombre as nombre, pe.cantidad as cantidad, pr.tipo as tipo, pr.precio as precio 
                                                                FROM pedidos pe
                                                                LEFT JOIN productos pr ON pe.producto_id = pr.id  WHERE pe.comanda_id=:id");
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

    public static function SubirImagen($foto, $idComanda){
        if(!file_exists("ImagenesComandas/")){
            mkdir("ImagenesComandas/",0777,true);
        }
        $nombre = $foto["name"];
        $destino = "ImagenesComandas/".$idComanda.".".$nombre;
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO imagenes (nombre,comanda_id) VALUES(:foto, :comanda_id)");
        $consulta->bindValue(':foto', $nombre);
        $consulta->bindValue(':comanda_id', $idComanda);
        $consulta->execute();
        if(move_uploaded_file($foto["tmp_name"], $destino)){
            return "Archivo subido con exito";
        }else{
            return "Ha ocurrido un error";
        }
    }
}