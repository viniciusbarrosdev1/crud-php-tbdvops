<?php

class Database {
    private static ?PDO $connection = null;

    public static function getConnection(): PDO {
        // Se já conectamos antes, reaproveita a conexão em vez de abrir outra
        if (self::$connection === null) {
            self::$connection = self::connection();
            self::criarTabela();
        }
        return self::$connection;
    }

    private static function connection(): PDO {
        $dns = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";

        for ($tentativa = 1; $tentativa <= 10 ; $tentativa++) {
            try {
                return new PDO($dns, getenv('DB_USER'), getenv('DB_PASSWORD'), [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (PDOException $e) {
                if ($tentativa === 10) {
                    throw $e;
                }
                sleep(2);
            }
        }

        throw new PDOException("Não foi possível conectar ao banco de dados.");
    }

    private static function criarTabela(): void {
        self::$connection->exec("CREATE TABLE IF NOT EXISTS clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            cpf VARCHAR(14) NOT NULL,
            descricao TEXT,
            data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
}