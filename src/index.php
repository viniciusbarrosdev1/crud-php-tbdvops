<?php
/**
 * Ponto de entrada. Le a acao, valida o que vem do formulario e escolhe a view.
 */
require_once __DIR__ . '/Controller/ClienteController.php';
require_once __DIR__ . '/helpers.php';

/**
 * Regras de cadastro. Devolve campo => mensagem para cada problema encontrado.
 */
function validarCliente(array $dados): array
{
    $erros = [];
    $nome  = trim((string) ($dados['nome'] ?? ''));
    $cpf   = digitos($dados['cpf'] ?? '');

    if ($nome === '') {
        $erros['nome'] = 'Informe o nome do cliente.';
    } elseif (mb_strlen($nome, 'UTF-8') < 3) {
        $erros['nome'] = 'O nome precisa de pelo menos 3 caracteres.';
    } elseif (mb_strlen($nome, 'UTF-8') > 100) {
        $erros['nome'] = 'O nome passa de 100 caracteres.';
    }

    if ($cpf === '') {
        $erros['cpf'] = 'Informe o CPF.';
    } elseif (strlen($cpf) !== 11) {
        $erros['cpf'] = 'O CPF precisa ter 11 dígitos.';
    } elseif (!cpfValido($cpf)) {
        $erros['cpf'] = 'Esse CPF não passa na verificação dos dígitos.';
    }

    if (mb_strlen((string) ($dados['descricao'] ?? ''), 'UTF-8') > 500) {
        $erros['descricao'] = 'A descrição passa de 500 caracteres.';
    }

    return $erros;
}

/** Normaliza o que chega do formulario antes de ir para o banco. */
function dadosDoFormulario(): array
{
    return [
        'nome'      => trim((string) ($_POST['nome'] ?? '')),
        'cpf'       => formatarCpf($_POST['cpf'] ?? ''),
        'descricao' => trim((string) ($_POST['descricao'] ?? '')),
    ];
}

function irPara(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

$controller = new ClienteController();
$acao       = $_GET['acao'] ?? 'listar';
$id         = (string) ($_GET['id'] ?? '');

try {
    switch ($acao) {

        case 'novo':
            $cliente = null;
            $valores = [];
            $erros   = [];
            require __DIR__ . '/View/form.php';
            break;

        case 'salvar':
            $valores = dadosDoFormulario();
            $erros   = validarCliente($valores);

            if ($erros !== []) {
                $cliente = null;
                require __DIR__ . '/View/form.php';
                break;
            }

            $controller->create($valores);
            break;

        case 'editar':
            $cliente = $controller->show($id);

            if ($cliente === null) {
                irPara('/index.php?erro=' . rawurlencode('Cliente não encontrado.'));
            }

            $valores = [];
            $erros   = [];
            require __DIR__ . '/View/form.php';
            break;

        case 'atualizar':
            $cliente = $controller->show($id);

            if ($cliente === null) {
                irPara('/index.php?erro=' . rawurlencode('Cliente não encontrado.'));
            }

            $valores = dadosDoFormulario();
            $erros   = validarCliente($valores);

            if ($erros !== []) {
                require __DIR__ . '/View/form.php';
                break;
            }

            $controller->update($id, $valores);
            break;

        case 'excluir':
            $controller->delete((string) ($_POST['id'] ?? $id));
            break;

        default:
            $clientes = $controller->listAll();
            require __DIR__ . '/View/listar.php';
    }
} catch (Throwable $falha) {
    irPara('/index.php?erro=' . rawurlencode($falha->getMessage()));
}
