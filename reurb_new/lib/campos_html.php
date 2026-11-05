<?php
/**
 * Geração do HTML de cada campo da ficha, conforme o tipo.
 */

declare(strict_types=1);

function campo_html(Crud $crud, string $nome, $valor, bool $novo, ?string $erro, bool $bloqueado = false): string
{
    $c  = $crud->campos[$nome];
    $id = 'f_' . $nome;

    // Campo de chave autoincremento na inclusão: não aparece
    if ($novo && $c['auto_increment']) {
        return '';
    }

    $readonly   = $bloqueado || $c['readonly'] || (!$novo && $c['readonly_edicao']);
    $invalido   = $erro ? ' is-invalid' : '';
    $exigido    = $c['obrigatorio'] && !$readonly && !($c['tipo'] === 'senha' && !$novo); // senha vazia na edição = manter
    $required   = $exigido ? ' required' : '';
    $asterisco  = $exigido ? '<span class="obrigatorio" title="obrigatório">*</span>' : '';
    $valorTexto = $valor === null ? '' : (string) $valor;

    $sufixo = preg_match('/[?:]$/u', $c['label']) ? '' : ':';
    $label = '<label for="' . e($id) . '">' . e($c['label']) . $sufixo . $asterisco . '</label>';
    $feedback = $erro ? '<div class="invalid-feedback d-block">' . e($erro) . '</div>' : '';

    // Somente leitura: mostra o valor formatado (fk/select/simnao mostram o texto)
    if ($readonly && $c['tipo'] !== 'json') {
        $mostrar = in_array($c['tipo'], ['fk', 'select', 'simnao'], true) ? $crud->exibir($nome, $valor) : $valorTexto;
        if ($c['tipo'] === 'senha') {
            $mostrar = '••••••';
        }
        $html = $c['tipo'] === 'textarea' || ($c['base'] === 'textarea' && $c['tipo'] !== 'foto')
            ? '<textarea class="form-control" id="' . e($id) . '" rows="2" readonly>' . e($mostrar) . '</textarea>'
            : '<input type="text" class="form-control" id="' . e($id) . '" value="' . e($mostrar) . '" readonly>';
        // Na edição, a chave segue no POST só como referência (o servidor não a altera)
        return envolver($c['col'], $label . $html . $feedback);
    }

    $attrNome = ' name="' . e($nome) . '" id="' . e($id) . '"' . $required;

    switch ($c['tipo']) {
        case 'json':
            $json = json_decode($valorTexto, true);
            $texto = $json === null ? $valorTexto : json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $html = '<pre class="reurb-json mb-0" id="' . e($id) . '">' . e($texto !== '' ? $texto : '—') . '</pre>';
            break;

        case 'senha':
            $html = '<input type="password" class="form-control' . $invalido . '"' . $attrNome . ' autocomplete="new-password"'
                  . ' placeholder="' . ($novo ? 'Mínimo de 6 caracteres' : 'Deixe em branco para manter a atual') . '"'
                  . ($novo ? ' required' : '') . '>';
            break;

        case 'textarea':
            $html = '<textarea class="form-control' . $invalido . '"' . $attrNome . ' rows="2">' . e($valorTexto) . '</textarea>';
            break;

        case 'select':
        case 'simnao':
        case 'fk':
            if ($c['tipo'] === 'fk') {
                $opcoes = $crud->opcoesFk($nome);
            } elseif ($c['tipo'] === 'simnao') {
                $opcoes = ['sim' => 'Sim', 'nao' => 'Não'];
            } else {
                $opcoes = $c['opcoes'] ?? [];
            }
            // Mantém o valor gravado exatamente como está no banco ("Sim", "sim", "SIM"...)
            $temAtual = $valorTexto === '';
            $html = '<select class="form-control' . $invalido . '"' . $attrNome . '>';
            $html .= '<option value="">Selecione...</option>';
            foreach ($opcoes as $v => $t) {
                $v = (string) $v;
                if ($valorTexto !== '' && !$temAtual && Crud::normalizar($v) === Crud::normalizar($valorTexto)) {
                    $v = $valorTexto;
                }
                $sel = ($valorTexto !== '' && $v === $valorTexto) ? ' selected' : '';
                if ($sel) {
                    $temAtual = true;
                }
                $html .= '<option value="' . e($v) . '"' . $sel . '>' . e($t) . '</option>';
            }
            if (!$temAtual && $c['tipo'] !== 'fk') {
                $html .= '<option value="' . e($valorTexto) . '" selected>' . e($valorTexto) . ' (valor importado)</option>';
            }
            $html .= '</select>';
            break;

        case 'foto':
        case 'ref':
            if ($c['tipo'] === 'foto') {
                $base  = (string) cfg('fotos_base_url', '');
                $href  = $valorTexto === '' ? '#' : (preg_match('#^(https?:)?//|^/#i', $valorTexto) ? $valorTexto : $base . $valorTexto);
                $icone = 'fa-image';
                $dica  = 'Abrir imagem';
                $modo  = 'foto';
            } else {
                $base  = url('form.php', ['t' => $c['ref']]) . '&id=';
                $href  = $valorTexto === '' ? '#' : $base . rawurlencode($valorTexto);
                $icone = 'fa-external-link-alt';
                $dica  = 'Abrir ficha vinculada';
                $modo  = 'ref';
            }
            $tipoInput = in_array($c['base'], ['inteiro', 'decimal'], true) ? 'number' : 'text';
            $alvo = $id . '_link';
            $desab = $valorTexto === '' ? ' disabled" aria-disabled="true' : '';
            $html = '<div class="input-group">'
                  . '<input type="' . $tipoInput . '" class="form-control' . $invalido . '"' . $attrNome
                  . ' value="' . e($valorTexto) . '"' . ($c['maxlength'] ? ' maxlength="' . $c['maxlength'] . '"' : '')
                  . ' data-link-base="' . e($base) . '" data-link-alvo="' . e($alvo) . '" data-link-modo="' . $modo . '">'
                  . '<div class="input-group-append"><a class="btn btn-outline-primary' . $desab . '" id="' . e($alvo) . '"'
                  . ' href="' . e($href) . '" target="_blank" rel="noopener" title="' . $dica . '"><i class="fas ' . $icone . '"></i></a></div>'
                  . '</div>';
            break;

        default:
            [$type, $extra] = match ($c['base']) {
                'inteiro'  => ['number', ' step="1"'],
                'decimal'  => ['number', ' step="' . ($c['escala'] > 0 ? '0.' . str_repeat('0', $c['escala'] - 1) . '1' : '1') . '"'],
                'data'     => ['date', ''],
                'datahora' => ['datetime-local', ' step="1"'],
                default    => ['text', $c['maxlength'] ? ' maxlength="' . $c['maxlength'] . '"' : ''],
            };
            if ($c['base'] === 'datahora' && $valorTexto !== '') {
                $valorTexto = str_replace(' ', 'T', substr($valorTexto, 0, 19));
            }
            if ($c['base'] === 'data' && $valorTexto !== '') {
                $valorTexto = substr($valorTexto, 0, 10);
            }
            if ($nome === 'email') {
                $type = 'email';
            }
            $html = '<input type="' . $type . '" class="form-control' . $invalido . '"' . $attrNome . $extra
                  . ' value="' . e($valorTexto) . '">';
    }

    return envolver($c['col'], $label . $html . $feedback);
}

function envolver(int $col, string $conteudo): string
{
    $col = max(1, min(12, $col));
    return '<div class="form-group col-md-' . $col . '">' . $conteudo . '</div>';
}
