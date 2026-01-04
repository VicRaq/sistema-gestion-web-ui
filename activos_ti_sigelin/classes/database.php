<?php
class Database {
    private $host = "root";
    private $dbName = "root"; 
    private $username = "root";
    private $password = "root";

    private $conn;

    public function dbConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbName};charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Fallo la conexión a la BD: " . $e->getMessage());
            echo "Error interno de la aplicación. Por favor, contacte a soporte.";
            exit();
        }
        return $this->conn;
    }
}
?>