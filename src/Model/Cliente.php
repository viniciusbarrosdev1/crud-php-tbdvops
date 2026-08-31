<?php
require_once __DIR__ . "/../Config/Database.php";

class ClienteModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    private static function generateUUID(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function create(array $dados): bool
    {
        try {
            $query = "INSERT INTO clientes (id, nome, cpf, descricao)VALUES (:id, :nome, :cpf, :descricao);";
            $stmt = $this->conn->prepare($query);


            $stmt->execute([
                ":id" => self::generateUUID(),
                ":nome" => $dados['nome'],
                ":cpf" => $dados['cpf'],
                ":descricao" => $dados['descricao'],
            ]);

            return true;
        } catch (Throwable $th) {
            throw $th;
        }
    }

    public function listAll(): ?array
    {
        try {
            $stmt = $this->conn->query("SELECT * FROM clientes ORDER BY data_cadastro DESC;");

            return $stmt->fetchAll();
        } catch (Throwable $th) {
            throw $th;
        }
    }

    public function getById(string $id): array
    {
        try {
            $query = "SELECT * FROM clientes WHERE id = :id;";
            $stmt  = $this->conn->prepare($query);

            $stmt->execute([
                ":id" => $id
            ]);

            $cliente = $stmt->fetch();

            return $cliente ?: null;
        } catch (Throwable $th) {
            throw $th;
        }
    }

    public function update(string $id, array $dados): bool
    {
        try {
            $query = "UPDATE clientes SET nome = :nome, cpf = :cpf, descricao = :descricao WHERE id = :id";
            $stmt = $this->conn->prepare($query);

            return $stmt->execute([
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'],
                'descricao' => $dados['descricao'] ?? null,
                'id' => $id,
            ]);
        } catch (Throwable $th) {
            throw $th;
        }
    }

    public function delete(string $id): bool
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM clientes WHERE id = :id");

            return $stmt->execute(['id' => $id]);
        } catch (Throwable $th) {
            throw $th;
        }
    }
}
