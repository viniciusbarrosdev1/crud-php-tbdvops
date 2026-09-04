<?php
/**
 * Fecha o documento, entrega os avisos flutuantes e o comportamento comum.
 */
$sucesso = $_GET['msg']  ?? '';
$falha   = $_GET['erro'] ?? '';
?>
</main>

<?php if ($sucesso !== '' || $falha !== ''): ?>
<div class="avisos" role="status" aria-live="polite">
    <?php if ($sucesso !== ''): ?>
    <div class="aviso aviso--ok">
        <span class="aviso__icone"><?= icone('certo', 16) ?></span>
        <span class="aviso__texto"><?= e($sucesso) ?></span>
        <button class="aviso__fechar" type="button" data-fechar-aviso aria-label="Fechar aviso"><?= icone('x', 13) ?></button>
    </div>
    <?php endif; ?>

    <?php if ($falha !== ''): ?>
    <div class="aviso aviso--ops">
        <span class="aviso__icone"><?= icone('alerta', 16) ?></span>
        <span class="aviso__texto"><?= e($falha) ?></span>
        <button class="aviso__fechar" type="button" data-fechar-aviso aria-label="Fechar aviso"><?= icone('x', 13) ?></button>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
(function () {
    // Tira msg/erro da barra de enderecos para o aviso nao voltar ao recarregar.
    if (location.search.includes('msg=') || location.search.includes('erro=')) {
        var limpa = new URL(location.href);
        limpa.searchParams.delete('msg');
        limpa.searchParams.delete('erro');
        history.replaceState(null, '', limpa.pathname + limpa.search + limpa.hash);
    }

    function dispensar(aviso) {
        if (!aviso || aviso.dataset.saindo === 'sim') return;
        aviso.dataset.saindo = 'sim';
        aviso.addEventListener('transitionend', function () { aviso.remove(); }, { once: true });
        setTimeout(function () { aviso.remove(); }, 400);
    }

    document.querySelectorAll('[data-fechar-aviso]').forEach(function (botao) {
        botao.addEventListener('click', function () { dispensar(botao.closest('.aviso')); });
    });

    document.querySelectorAll('.aviso').forEach(function (aviso) {
        setTimeout(function () { dispensar(aviso); }, 5000);
    });
})();
</script>
</body>
</html>
