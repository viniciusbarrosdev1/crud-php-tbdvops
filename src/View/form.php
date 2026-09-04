<?php
/**
 * Formulario de cadastro e de edicao.
 * Espera: $cliente (array|null), $valores (array), $erros (array campo => mensagem).
 */
require_once __DIR__ . '/../helpers.php';

$cliente  = $cliente  ?? null;
$valores  = $valores  ?? [];
$erros    = $erros    ?? [];
$editando = $cliente !== null;

$nome      = (string) ($valores['nome']      ?? $cliente['nome']      ?? '');
$cpf       = (string) ($valores['cpf']       ?? $cliente['cpf']       ?? '');
$descricao = (string) ($valores['descricao'] ?? $cliente['descricao'] ?? '');

$acao = $editando
    ? '/index.php?acao=atualizar&id=' . rawurlencode((string) $cliente['id'])
    : '/index.php?acao=salvar';

$titulo   = $editando ? 'Editar cliente' : 'Novo cliente';
$local    = $titulo;
$estreita = true;
require __DIR__ . '/partials/topo.php';
?>

<div class="cabecalho">
    <div>
        <h1 class="cabecalho__titulo"><?= $editando ? 'Editar cliente' : 'Novo cliente' ?></h1>
        <p class="cabecalho__apoio">
            <?= $editando
                ? 'Ajuste os dados e salve. O histórico de cadastro continua o mesmo.'
                : 'Nome e CPF bastam para começar. A descrição entra quando você quiser.' ?>
        </p>
    </div>
</div>

<?php if ($erros !== []): ?>
<div class="aviso-erro" role="alert">
    <span><?= icone('alerta', 16) ?></span>
    <span>
        <?= count($erros) === 1
            ? 'Falta acertar um campo antes de salvar.'
            : 'Faltam acertar ' . count($erros) . ' campos antes de salvar.' ?>
    </span>
</div>
<?php endif; ?>

<form class="painel" method="post" action="<?= e($acao) ?>" novalidate>

    <div class="previa">
        <span class="monograma monograma--grande" id="previaIniciais" aria-hidden="true">
            <?= $nome !== '' ? e(iniciais($nome)) : '?' ?>
        </span>
        <div>
            <div class="previa__nome" id="previaNome" data-vazio="<?= $nome === '' ? 'sim' : 'nao' ?>">
                <?= $nome !== '' ? e($nome) : 'Nome do cliente' ?>
            </div>
            <div class="mono previa__cpf" id="previaCpf"><?= $cpf !== '' ? e(formatarCpf($cpf)) : '000.000.000-00' ?></div>
        </div>
    </div>

    <div class="form__corpo">

        <div class="campo" data-erro="<?= isset($erros['nome']) ? 'sim' : 'nao' ?>">
            <label class="campo__rotulo" for="nome">Nome completo</label>
            <input class="entrada" type="text" id="nome" name="nome" maxlength="100"
                   value="<?= e($nome) ?>" autocomplete="name" autofocus
                   <?= isset($erros['nome']) ? 'aria-invalid="true" aria-describedby="erroNome"' : '' ?>>
            <?php if (isset($erros['nome'])): ?>
                <span class="campo__erro" id="erroNome"><?= icone('alerta', 13) ?> <?= e($erros['nome']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo" data-erro="<?= isset($erros['cpf']) ? 'sim' : 'nao' ?>">
            <label class="campo__rotulo" for="cpf">CPF</label>
            <span class="campo__ajuda">Onze dígitos. A pontuação entra sozinha enquanto você digita.</span>
            <input class="entrada entrada--mono" type="text" id="cpf" name="cpf" inputmode="numeric" maxlength="14"
                   value="<?= e(formatarCpf($cpf)) ?>"
                   <?= isset($erros['cpf']) ? 'aria-invalid="true" aria-describedby="erroCpf"' : '' ?>>
            <?php if (isset($erros['cpf'])): ?>
                <span class="campo__erro" id="erroCpf"><?= icone('alerta', 13) ?> <?= e($erros['cpf']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label class="campo__rotulo" for="descricao">
                Descrição <span class="campo__opcional">opcional</span>
            </label>
            <span class="campo__ajuda">Uma nota curta para lembrar quem é este cliente.</span>
            <textarea class="entrada" id="descricao" name="descricao" rows="4" maxlength="500"><?= e($descricao) ?></textarea>
            <span class="contador" id="contador"><?= mb_strlen($descricao, 'UTF-8') ?>/500</span>
        </div>

    </div>

    <div class="form__rodape">
        <a class="btn btn--fantasma" href="/index.php"><?= icone('voltar', 15) ?> Cancelar</a>
        <button class="btn btn--principal" type="submit">
            <?= $editando ? 'Salvar alterações' : 'Cadastrar cliente' ?>
        </button>
    </div>

</form>

<script>
(function () {
    var nome      = document.getElementById('nome');
    var cpf       = document.getElementById('cpf');
    var descricao = document.getElementById('descricao');

    var previaIniciais = document.getElementById('previaIniciais');
    var previaNome     = document.getElementById('previaNome');
    var previaCpf      = document.getElementById('previaCpf');
    var contador       = document.getElementById('contador');

    function iniciaisDe(valor) {
        var partes = valor.trim().split(/\s+/).filter(Boolean);
        if (partes.length === 0) return '?';
        var primeira = partes[0].charAt(0);
        var ultima   = partes.length > 1 ? partes[partes.length - 1].charAt(0) : '';
        return (primeira + ultima).toUpperCase();
    }

    function mascarar(valor) {
        var n = valor.replace(/\D+/g, '').slice(0, 11);
        if (n.length > 9) return n.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        if (n.length > 6) return n.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
        if (n.length > 3) return n.replace(/(\d{3})(\d{1,3})/, '$1.$2');
        return n;
    }

    nome.addEventListener('input', function () {
        var valor = nome.value.trim();
        previaIniciais.textContent = iniciaisDe(nome.value);
        previaNome.textContent = valor === '' ? 'Nome do cliente' : valor;
        previaNome.dataset.vazio = valor === '' ? 'sim' : 'nao';
    });

    cpf.addEventListener('input', function () {
        // Mantem o cursor no fim quando a mascara cresce, que e o caso comum.
        var noFim = cpf.selectionStart === cpf.value.length;
        cpf.value = mascarar(cpf.value);
        if (noFim) cpf.setSelectionRange(cpf.value.length, cpf.value.length);
        previaCpf.textContent = cpf.value === '' ? '000.000.000-00' : cpf.value;
    });

    descricao.addEventListener('input', function () {
        contador.textContent = descricao.value.length + '/500';
    });
})();
</script>

<?php require __DIR__ . '/partials/rodape.php'; ?>
