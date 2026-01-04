<?php
require_once 'database.php';

class UsuarioSistema {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->dbConnection();
    }

    // Acceder a conexion PDO
    public function getConnection() {
        return $this->conn;
    }

    // Permite ejecutar consultas preparadas 
    public function runQuery($sql) {
        return $this->conn->prepare($sql);
    }

    public function insert($nombre, $correo, $rol, $contrasena_plana) {
        try {
            // Patrón de seguridad: Hashing de la contraseña 
            $contrasena_hash = password_hash($contrasena_plana, PASSWORD_DEFAULT);

            // CORRECCIÓN: Tabla 'UsuarioSistema' -> 'usuariosistema' (minúsculas)
            $stmt = $this->conn->prepare("INSERT INTO usuariosistema (nombre, correo_electronico, contrasena_hash, rol) VALUES (:nombre, :correo, :hash, :rol)");

            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":rol", $rol);
            $stmt->bindParam(":hash", $contrasena_hash); // Usamos el hash
            
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Error al insertar usuario de sistema: " . $e->getMessage());
            return false;
        }
    }

    public function update($nombre, $correo, $rol, $id, $contrasena_plana = null) {
        try {
            // CORRECCIÓN: Tabla 'UsuarioSistema' -> 'usuariosistema' (minúsculas)
            $sql = "UPDATE usuariosistema SET nombre = :nombre, correo_electronico = :correo, rol = :rol";
            
            // Añadida validación !empty para no hashear una cadena vacía
            if ($contrasena_plana !== null && !empty($contrasena_plana)) {
                $contrasena_hash = password_hash($contrasena_plana, PASSWORD_DEFAULT);
                $sql .= ", contrasena_hash = :hash";
            }
            $sql .= " WHERE id_usuario = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":rol", $rol);
            $stmt->bindParam(":id", $id);

            if ($contrasena_plana !== null && !empty($contrasena_plana)) {
                $stmt->bindParam(":hash", $contrasena_hash);
            }

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Error al actualizar usuario de sistema: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            // CORRECCIÓN: Tabla 'UsuarioSistema' -> 'usuariosistema' (minúsculas)
            $stmt = $this->conn->prepare("DELETE FROM usuariosistema WHERE id_usuario = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Error al eliminar usuario de sistema: " . $e->getMessage());
            return false;
        }
    }

    // Funcion de redireccion
    public function redirect($url) {
        header("Location: $url");
        exit();
    }

    // Verificacion login para seguridad
    public function login($correo, $contrasena_plana) {
        try {
            // CORRECCIÓN: Tabla 'UsuarioSistema' -> 'usuariosistema' (minúsculas)
            // MEJORA: Seleccionamos 'rol' y 'nombre' para la sesión
            $stmt = $this->conn->prepare("SELECT id_usuario, nombre, contrasena_hash, rol FROM usuariosistema WHERE correo_electronico = :correo LIMIT 1");
            $stmt->bindParam(":correo", $correo);
            $stmt->execute();
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userRow && password_verify($contrasena_plana, $userRow['contrasena_hash'])) {
                // MEJORA: Devolvemos el array completo del usuario
                return $userRow; 
            }
            return false; // Login fallido
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }
}
?>