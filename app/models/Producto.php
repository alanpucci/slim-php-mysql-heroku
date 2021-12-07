<?php

class Producto
{
    public $id;
    public $precio;
    public $nombre;
    public $tipo;
    public $sector;

    public function crearProducto()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta_sector = $objAccesoDatos->prepararConsulta("SELECT id FROM sectores WHERE nombre=:sector");
        $consulta_sector->bindValue(':sector', $this->sector);
        $consulta_sector->execute();
        $sector = $consulta_sector->fetch(PDO::FETCH_ASSOC);
        if($sector && $sector["id"]){
            $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO productos (precio, nombre, tipo, sector_id) VALUES (:precio, :nombre, :tipo, :sector_id)");
            $consulta->bindValue(':precio', $this->precio);
            $consulta->bindValue(':nombre', $this->nombre);
            $consulta->bindValue(':tipo', $this->tipo);
            $consulta->bindValue(':sector_id', $sector["id"]);
            $consulta->execute();
        }else{
            throw new Exception("Sector inexistente");
        }
        return "Producto creado exitosamente";
    }

    public static function obtenerTodos($sector)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos p 
                                                        LEFT JOIN sectores s ON p.sector_id = s.id
                                                        WHERE s.nombre=:sector");
        $consulta->bindValue(':sector', $sector);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
    }
}