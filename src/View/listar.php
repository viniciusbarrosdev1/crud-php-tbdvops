<?php
/**
 * Listagem de clientes.
 * Espera: $clientes (array de linhas do banco).
 */
require_once __DIR__ . '/../helpers.php';

$clientes = $clientes ?? [];
$total    = count($clientes);

$titulo = 'Clientes';
$local  = 'Clientes';
require __DIR__ . '/partials/topo.php';
?>

<div class="cabecalho">
    <div>
        <h1 class="cabecalho__titulo">Clientes</h1>
        <p class="cabecalho__apoio">Todo mundo que já passou pelo cadastro, do mais recente ao mais antigo.</p>
    </div>
    <div class="cabecalho__acoes">
        <a class="btn btn--principal" href="/index.php?acao=novo">
            <?= icone('mais', 15) ?> Novo cliente
        </a>
    </div>
</div>

<?php if ($total === 0): ?>

    <div class="painel">
        <div class="vazio">
            <span class="vazio__icone"><?= icone('clientes', 22) ?></span>
            <h2 class="vazio__titulo">Nenhum cliente por aqui ainda</h2>
            <p class="vazio__texto">Assim que você cadastrar o primeiro, ele aparece nesta lista com nome, CPF e data de entrada.</p>
            <a class="btn btn--principal" href="/index.php?acao=novo">
                <?= icone('mais', 15) ?> Cadastrar o primeiro
            </a>
        </div>
    </div>

<?php else: ?>

    <div class="ferramentas">
        <div class="busca">
            <span class="busca__icone"><?= icone('lupa', 15) ?></span>
            <label class="sr-only" for="busca">Filtrar por nome ou CPF</label>
            <input class="busca__campo" id="busca" type="search" autocomplete="off"
                   placeholder="Filtrar por nome ou CPF">
            <button class="busca__limpar" type="button" id="limparBusca" aria-label="Limpar filtro" hidden>
                <?= icone('x', 13) ?>
            </button>
        </div>
        <span class="contagem" id="contagem" data-total="<?= $total ?>">
            <?= $total ?> <?= $total === 1 ? 'cliente' : 'clientes' ?>
        </span>
    </div>

    <div class="painel">
        <table class="tabela">
            <thead>
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">CPF</th>
                    <th scope="col">Descrição</th>
                    <th scope="col">Cadastro</th>
                    <th scope="col" class="col-acoes"><span class="sr-only">Ações</span></th>
                </tr>
            </thead>
            <tbody class="cascata" id="listaClientes">
                <?php foreach (array_values($clientes) as $i => $cliente): ?>
                <tr style="--i: <?= min($i, 12) ?>"
                    data-nome="<?= e(mb_strtolower($cliente['nome'], 'UTF-8')) ?>"
                    data-cpf="<?= e(digitos($cliente['cpf'])) ?>">

                    <td>
                        <div class="identidade">
                            <span class="monograma" aria-hidden="true"><?= e(iniciais($cliente['nome'])) ?></span>
                            <span class="identidade__nome"><?= e($cliente['nome']) ?></span>
                        </div>
                    </td>

                    <td><span class="mono"><?= e(formatarCpf($cliente['cpf'])) ?></span></td>

                    <td>
                        <?php if (trim((string) $cliente['descricao']) !== ''): ?>
                            <span class="descricao" title="<?= e($cliente['descricao']) ?>"><?= e($cliente['descricao']) ?></span>
                        <?php else: ?>
                            <span class="vazio-celula">Sem descrição</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <span class="mono" title="<?= e(dataCompleta($cliente['data_cadastro'] ?? null)) ?>">
                            <?= e(dataRelativa($cliente['data_cadastro'] ?? null)) ?>
                        </span>
                    </td>

                    <td class="col-acoes">
                        <div class="linha__acoes">
                            <a class="acao" href="/index.php?acao=editar&id=<?= e($cliente['id']) ?>"
                               aria-label="Editar <?= e($cliente['nome']) ?>" title="Editar">
                                <?= icone('lapis', 15) ?>
                            </a>
                            <button class="acao acao--perigo" type="button"
                                    data-excluir="<?= e($cliente['id']) ?>"
                                    data-nome="<?= e($cliente['nome']) ?>"
                                    aria-label="Excluir <?= e($cliente['nome']) ?>" title="Excluir">
                                <?= icone('lixeira', 15) ?>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="vazio" id="semResultado" hidden>
            <span class="vazio__icone"><?= icone('lupa', 22) ?></span>
            <h2 class="vazio__titulo">Nada bateu com esse filtro</h2>
            <p class="vazio__texto">Confira a grafia do nome ou tente digitar só os números do CPF.</p>
            <button class="btn btn--neutro" type="button" id="limparBusca2">Limpar filtro</button>
        </div>
    </div>

    <dialog class="dialogo" id="dialogoExcluir" aria-labelledby="tituloExcluir">
        <form method="post" action="/index.php?acao=excluir">
            <input type="hidden" name="id" id="idParaExcluir">
            <div class="dialogo__corpo">
                <h2 class="dialogo__titulo" id="tituloExcluir">Excluir este cliente?</h2>
                <p class="dialogo__texto">
                    <span class="dialogo__alvo" id="nomeParaExcluir"></span> sai do cadastro na hora e não dá para desfazer.
                </p>
            </div>
            <div class="dialogo__rodape">
                <button class="btn btn--neutro" type="button" id="cancelarExcluir">Cancelar</button>
                <button class="btn btn--perigo" type="submit"><?= icone('lixeira', 15) ?> Excluir</button>
            </div>
        </form>
    </dialog>

    <script>
    (function () {
        var campo     = document.getElementById('busca');
        var limpar    = document.getElementById('limparBusca');
        var limpar2   = document.getElementById('limparBusca2');
        var contagem  = document.getElementById('contagem');
        var semResult = document.getElementById('semResultado');
        var tabela    = document.querySelector('.tabela');
        var linhas    = Array.prototype.slice.call(document.querySelectorAll('#listaClientes tr'));

        function rotular(n) {
            return n + (n === 1 ? ' cliente' : ' clientes');
        }

        function filtrar() {
            var termo    = campo.value.trim().toLowerCase();
            var numeros  = termo.replace(/\D+/g, '');
            var visiveis = 0;

            linhas.forEach(function (linha) {
                var casa = termo === ''
                    || linha.dataset.nome.indexOf(termo) !== -1
                    || (numeros !== '' && linha.dataset.cpf.indexOf(numeros) !== -1);

                linha.hidden = !casa;
                if (casa) visiveis++;
            });

            limpar.hidden    = termo === '';
            semResult.hidden = visiveis !== 0;
            tabela.hidden    = visiveis === 0;
            contagem.textContent = termo === ''
                ? rotular(Number(contagem.dataset.total))
                : rotular(visiveis) + ' de ' + contagem.dataset.total;
        }

        function zerar() {
            campo.value = '';
            filtrar();
            campo.focus();
        }

        campo.addEventListener('input', filtrar);
        limpar.addEventListener('click', zerar);
        limpar2.addEventListener('click', zerar);

        campo.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && campo.value !== '') {
                evento.preventDefault();
                zerar();
            }
        });

        // Barra de busca no atalho "/", como em ferramentas de uso diario.
        document.addEventListener('keydown', function (evento) {
            if (evento.key === '/' && document.activeElement !== campo) {
                evento.preventDefault();
                campo.focus();
                campo.select();
            }
        });

        var dialogo = document.getElementById('dialogoExcluir');
        var alvoId  = document.getElementById('idParaExcluir');
        var alvoNome = document.getElementById('nomeParaExcluir');

        document.querySelectorAll('[data-excluir]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                alvoId.value = botao.dataset.excluir;
                alvoNome.textContent = botao.dataset.nome;
                dialogo.showModal();
            });
        });

        document.getElementById('cancelarExcluir').addEventListener('click', function () {
            dialogo.close();
        });
    })();
    </script>

<?php endif; ?>

<?php require __DIR__ . '/partials/rodape.php'; ?>
