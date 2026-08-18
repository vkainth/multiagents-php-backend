<?php
namespace Src\System;

class DatabaseConnector {

    private $dbConnection = null;
    private $dbConnectionMLS = null;
    private $dbConnectionLES = null;

    public function __construct()
    {
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $db   = getenv('DB_DATABASE');
        $user = getenv('DB_USERNAME');
        $pass = getenv('DB_PASSWORD');

        $dbMLS   = getenv('DB_DATABASE_MLS');
        $userMLS = getenv('DB_USERNAME_MLS');
        $passMLS = getenv('DB_PASSWORD_MLS');
        $dbHostMLS = getenv('DB_HOST_MLS');
        
        $dbLES	= getenv('DB_DATABASE_LES');
        $userLES= getenv('DB_USERNAME_LES');
        $passLES= getenv('DB_PASSWORD_LES');

        try {
            $this->dbConnection = new \PDO(
                "mysql:host=$host;port=$port;charset=utf8mb4;dbname=$db",
                $user,
                $pass
            );

            $this->dbConnectionMLS = new \PDO(
                "mysql:host=$dbHostMLS;port=$port;charset=utf8mb4;dbname=$dbMLS",
                $userMLS,
                $passMLS
            );
            
            $this->dbConnectionLES = new \PDO(
                "mysql:host=$host;port=$port;charset=utf8mb4;dbname=$dbLES",
                $userLES,
                $passLES
            );

        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->dbConnection;
    }

    public function getConnectionMLS()
    {
        return $this->dbConnectionMLS;
    }
    
    public function getConnectionLES()
    {
        return $this->dbConnectionLES;
    }
}