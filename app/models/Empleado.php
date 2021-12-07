<?php

class Empleado
{
    public $id;
    public $fecha_creacion;
    public $puesto;
    public $nombre;
    public $sector;
    public $usuario;
    public $clave;

    public function crearEmpleado()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $puesto = $objAccesoDatos->prepararConsulta("SELECT id FROM puestos WHERE nombre=:puesto");
        $puesto->bindValue(':puesto', $this->puesto);
        $puesto->execute();
        $puesto_result = $puesto->fetch(PDO::FETCH_ASSOC);
        $sector = $objAccesoDatos->prepararConsulta("SELECT id FROM sectores WHERE nombre=:sector");
        $sector->bindValue(':sector', $this->sector);
        $sector->execute();
        $sector_result = $sector->fetch(PDO::FETCH_ASSOC);
        if($puesto_result && $puesto_result["id"] && $sector_result && $sector_result["id"]){
            $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO empleados (fecha_creacion, puesto_id, nombre, sector_id, usuario, clave) 
                                                            VALUES (:fecha_creacion, :puesto, :nombre, :sector, :usuario, :clave)");
            $date = new DateTime("now");
            $fechaCreacion = $date->format('Y-m-d H:i:s');
            $claveHash = password_hash($this->clave, PASSWORD_DEFAULT);
            $consulta->bindValue(':fecha_creacion', $fechaCreacion);
            $consulta->bindValue(':puesto', $puesto_result["id"]);
            $consulta->bindValue(':nombre', $this->nombre);
            $consulta->bindValue(':sector', $sector_result["id"]);
            $consulta->bindValue(':usuario', $this->usuario);
            $consulta->bindValue(':clave', $claveHash);
            $consulta->execute();
            return "Empleado creado exitosamente";
        }
        return "Error al crear el empleado";
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT e.id, e.nombre, e.usuario, e.fecha_creacion, s.nombre as sector, p.nombre as puesto FROM empleados e 
                                                        LEFT JOIN puestos p ON e.puesto_id = p.id
                                                        LEFT JOIN sectores s ON e.sector_id = s.id");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerUno($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM empleados WHERE id=:id");
        $consulta->bindValue(':id', $id);
        $consulta->execute();
        $empleado = $consulta->fetchAll(PDO::FETCH_CLASS, "Empleado");
        return $empleado;
    }

    public function modificarEmpleado()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE empleados SET estado=:estado WHERE id=:id");
        $consulta->bindValue(':id', $this->id);
        $consulta->bindValue(':estado', $this->estado);
        $consulta->execute();
    }

    public function validarUsuario(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT e.nombre, usuario, clave, p.nombre as puesto, s.nombre as sector, e.estado FROM empleados e 
                                                        LEFT JOIN puestos p ON e.puesto_id = p.id 
                                                        LEFT JOIN sectores s ON e.sector_id = s.id
                                                        WHERE usuario=:usuario");
        $consulta->bindValue(':usuario', $this->usuario);
        $consulta->execute();
        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);
        if($usuario["estado"] == "inactivo"){
            throw new Exception("Empleado dado de baja");
        }
        if(password_verify($this->clave, $usuario["clave"])){
            return array('puesto'=>$usuario["puesto"],'sector'=>$usuario["sector"], 'nombre'=>$usuario["nombre"]);
        }
    }
}