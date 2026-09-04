<?php
/**
 * Abertura do documento e barra superior.
 * Espera: $titulo (string), $local (string, opcional).
 */
require_once __DIR__ . '/../../helpers.php';

$titulo = $titulo ?? 'Clientes';
$local  = $local  ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?= e($titulo) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/public/assets/app.css">
</head>
<body>

<header class="topo">
    <a class="topo__marca" href="/index.php">
        <span class="topo__selo"><?= icone('clientes', 15) ?></span>
        Cadastro
    </a>
    <?php if ($local !== ''): ?>
        <span class="topo__sep" aria-hidden="true"></span>
        <span class="topo__local"><?= e($local) ?></span>
    <?php endif; ?>
</header>

<main class="pagina<?= !empty($estreita) ? ' pagina--estreita' : '' ?>">
