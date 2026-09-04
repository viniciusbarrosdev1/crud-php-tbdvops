<?php
require_once __DIR__ . "/../Model/Cliente.php";

class ClienteController {
    private Cliente $clienteModel;

    public function __construct() {
        $this->clienteModel = new Cliente();
    }

    private function redirect(string $location): void {
        header("Location: $location");
        exit;
    }

    public function create(array $dados): void {
        try {
            $this->clienteModel->create($dados);
            $this->redirect("index.php?msg=Cliente criado com sucesso.");
        } catch (Throwable $th) {
            $this->redirect("index.php?erro=" . urlencode($th->getMessage()));
        }
    }

    public function listAll(): array {
        return $this->clienteModel->listAll() ?? [];
    }

    public function show(string $id): ?array {
        return $this->clienteModel->getById($id);
    }

    public function update(string $id, array $dados): void {
        try {
            $this->clienteModel->update($id, $dados);
            $this->redirect("index.php?msg=Cliente atualizado com sucesso.");
        } catch (Throwable $th) {
            $this->redirect("index.php?erro=" . urlencode($th->getMessage()));
        }
    }

    public function delete(string $id): void {
        try {
            $this->clienteModel->delete($id);
            $this->redirect("index.php?msg=Cliente removido com sucesso.");
        } catch (Throwable $th) {
            $this->redirect("index.php?erro=" . urlencode($th->getMessage()));
        }
    }
}