<?php

class Producto
{
    public $id;
    public $precio;
    public $nombre;
    public $tipo;

    public function crearProducto()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO productos (precio, nombre, tipo) VALUES (:precio, :nombre, :tipo)");
        $consulta->bindValue(':precio', $this->precio);
        $consulta->bindValue(':nombre', $this->nombre);
        $consulta->bindValue(':tipo', $this->tipo);
        $consulta->execute();
        return "Producto creado exitosamente";
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
    }
}