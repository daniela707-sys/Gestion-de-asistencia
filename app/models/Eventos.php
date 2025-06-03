<?php
class eventos {
    private $conn;
    private $table = "eventos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        try {
            $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            throw $e;
        }
    }


    public function crear($nombre, $descripcion, $fecha, $hora, $duracion, $imagen) {
        $query = "INSERT INTO " . $this->table . " (nombre, descripcion, fecha, hora, duracion, imagen)
                  VALUES (:nombre, :descripcion, :fecha, :hora, :duracion, :imagen)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ":nombre" => $nombre,
            ":descripcion" => $descripcion,
            ":fecha" => $fecha,
            ":hora" => $hora,
            ":duracion" => $duracion,
            ":imagen" => $imagen
        ]);
    }

    public function actualizar($id, $nombre, $descripcion, $fecha, $hora, $duracion, $imagen = null) {
        $query = "UPDATE " . $this->table . " SET nombre = :nombre, descripcion = :descripcion, fecha = :fecha, hora = :hora, duracion = :duracion";
        if ($imagen) {
            $query .= ", imagen = :imagen";
        }
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $params = [
            ":id" => $id,
            ":nombre" => $nombre,
            ":descripcion" => $descripcion,
            ":fecha" => $fecha,
            ":hora" => $hora,
            ":duracion" => $duracion,
            
        ];
        if ($imagen) {
            $params[":imagen"] = $imagen;
        }

        return $stmt->execute($params);
    }

   

    public function eliminar($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([":id" => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}
    