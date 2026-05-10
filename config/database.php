<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "sistema_aluguer";
    private $username = "root";
    private $password = "";
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=3307;dbname=" . $this->db_name . ";charset=utf8mb4",
				$this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            die("Erro de conexão: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
}
?>