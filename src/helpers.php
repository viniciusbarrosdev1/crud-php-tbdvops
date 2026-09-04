<?php

/**
 * Funcoes de apoio das views.
 */

/** Escapa texto para HTML. */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Icones da familia Tabler Icons (MIT), tracado 1.5 em todo o app.
 * Uma familia so, nunca desenhada a mao.
 */
function icone(string $nome, int $tamanho = 16): string
{
    $formas = [
        'clientes'  => '<path d="M9 7a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7"/><path d="M3 20v-1.5A3.5 3.5 0 0 1 6.5 15h5a3.5 3.5 0 0 1 3.5 3.5V20"/><path d="M16.5 7.3a3.5 3.5 0 0 1 0 6.4"/><path d="M21 20v-1.5a3.5 3.5 0 0 0-2.6-3.4"/>',
        'mais'      => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'lupa'      => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m20 20-4.9-4.9"/>',
        'x'         => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'lapis'     => '<path d="M4 20h4L18.5 9.5a2.83 2.83 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'lixeira'   => '<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="m5 7 1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12"/><path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>',
        'certo'     => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-4.5"/>',
        'alerta'    => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4.5"/><path d="M12 16h.01"/>',
        'voltar'    => '<path d="M5 12h14"/><path d="m5 12 6 6"/><path d="m5 12 6-6"/>',
    ];

    $forma = $formas[$nome] ?? '';

    return sprintf(
        '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
        . ' stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%2$s</svg>',
        $tamanho,
        $forma
    );
}

/** Duas iniciais a partir do nome. Serve de avatar sem foto falsa. */
function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    if ($partes === []) {
        return '?';
    }

    $primeira = mb_substr($partes[0], 0, 1, 'UTF-8');
    $ultima   = count($partes) > 1 ? mb_substr(end($partes), 0, 1, 'UTF-8') : '';

    return mb_strtoupper($primeira . $ultima, 'UTF-8');
}

/** Deixa so os digitos. */
function digitos(?string $valor): string
{
    return preg_replace('/\D+/', '', (string) $valor) ?? '';
}

/** 12345678909 vira 123.456.789-09. */
function formatarCpf(?string $cpf): string
{
    $numeros = digitos($cpf);

    if (strlen($numeros) !== 11) {
        return (string) $cpf;
    }

    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $numeros);
}

/** Valida o CPF pelos dois digitos verificadores. */
function cpfValido(?string $cpf): bool
{
    $numeros = digitos($cpf);

    if (strlen($numeros) !== 11 || preg_match('/^(\d)\1{10}$/', $numeros)) {
        return false;
    }

    foreach ([9, 10] as $posicao) {
        $soma = 0;

        for ($i = 0; $i < $posicao; $i++) {
            $soma += (int) $numeros[$i] * ($posicao + 1 - $i);
        }

        $resto = ($soma * 10) % 11;
        $digito = $resto === 10 ? 0 : $resto;

        if ($digito !== (int) $numeros[$posicao]) {
            return false;
        }
    }

    return true;
}

/** Data completa, para o atributo title. */
function dataCompleta(?string $data): string
{
    if (!$data) {
        return '';
    }

    $quando = date_create($data);

    return $quando ? $quando->format('d/m/Y \a\s H:i') : (string) $data;
}

/** Data curta e legivel. Recente vira tempo relativo. */
function dataRelativa(?string $data): string
{
    if (!$data) {
        return '';
    }

    $quando = date_create($data);

    if (!$quando) {
        return (string) $data;
    }

    $segundos = time() - $quando->getTimestamp();

    if ($segundos < 60) {
        return 'agora';
    }

    if ($segundos < 3600) {
        return 'há ' . (int) ($segundos / 60) . ' min';
    }

    if ($segundos < 86400) {
        return 'há ' . (int) ($segundos / 3600) . ' h';
    }

    if ($segundos < 604800) {
        return 'há ' . (int) ($segundos / 86400) . ' d';
    }

    $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    return $quando->format('j') . ' ' . $meses[(int) $quando->format('n') - 1] . ' ' . $quando->format('Y');
}
