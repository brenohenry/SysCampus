<?php

class Database
{
    private $host = "localhost";
    private $database = "syscampus";
    private $username = "root";
    private $password = "";

    public function conectar()
    {
        try {

            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4",
                $this->username,
                $this->password
            );

            $pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $pdo->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

            return $pdo;

        } catch (PDOException $e) {

            die(
                "Erro ao conectar ao banco de dados: "
                . $e->getMessage()
            );
        }
    }
}
