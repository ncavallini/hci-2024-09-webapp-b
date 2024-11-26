<?php
class DBConnection {

    private static DBConnection $instance;
    public PDO $connection;
    

    private function __construct() {
        $this->connection = new PDO($this->get_connection_string(), $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [PDO::FETCH_ASSOC]);        
    }

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new DBConnection();
        }
        return self::$instance;
    }

    public static function get_connection() {
        return self::get_instance()->connection;
    }
    private function get_connection_string(): string {
        return 'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'];
    }

}
?>