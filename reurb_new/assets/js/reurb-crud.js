/* Comportamentos do CRUD REURB */
(function ($) {
    'use strict';

    // Abre a confirmação de exclusão preenchendo tabela, id e descrição
    $(document).on('click', '[data-excluir-id]', function (ev) {
        ev.preventDefault();
        var $b = $(this), $m = $('#modalExcluir');
        $m.find('input[name=t]').val($b.data('excluir-t'));
        $m.find('input[name=id]').val($b.data('excluir-id'));
        $m.find('[data-alvo=descricao]').text($b.data('excluir-desc'));
        $m.modal('show');
    });

    // Botões "abrir" de campos de foto/referência acompanham o valor digitado
    $(document).on('input change', '[data-link-base]', function () {
        var $i = $(this), v = $.trim($i.val());
        var $a = $('#' + $i.data('link-alvo'));
        if (v === '') {
            $a.addClass('disabled').attr('aria-disabled', 'true').attr('href', '#');
        } else {
            var base = String($i.data('link-base'));
            var href;
            if ($i.data('link-modo') === 'ref') {
                href = base + encodeURIComponent(v);                       // ficha de outra tabela
            } else {
                href = /^(https?:)?\/\//i.test(v) || v.charAt(0) === '/' ? v : base + v;   // foto
            }
            $a.removeClass('disabled').removeAttr('aria-disabled').attr('href', href);
        }
    });

    // Aviso ao sair da ficha com alterações não salvas
    var $ficha = $('form.reurb-ficha'), alterada = false;
    $ficha.on('input change', ':input', function () { alterada = true; });
    $ficha.on('submit', function () { alterada = false; });
    $(window).on('beforeunload', function () { if (alterada) { return 'Há alterações não salvas.'; } });

    // Abas da ficha: lembra a aba aberta (por tela + registro) para voltar nela depois de salvar
    var $abas = $('.reurb-abas');
    if ($abas.length) {
        var chave = 'reurb-aba:' + $abas.data('memoria');
        var ler = function () { try { return window.sessionStorage.getItem(chave); } catch (e) { return null; } };
        var gravar = function (v) { try { window.sessionStorage.setItem(chave, v); } catch (e) { /* sem armazenamento */ } };

        // Com erro de validação o servidor já abriu a aba certa; senão, reabre a última usada
        if (String($abas.data('forcar')) !== '1') {
            var salva = ler();
            if (salva && $abas.find('a[href="#' + salva + '"]').length) {
                $abas.find('a[href="#' + salva + '"]').tab('show');
            }
        } else {
            gravar($abas.find('a.active').attr('href').substring(1));
        }
        $abas.on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
            gravar(this.getAttribute('href').substring(1));
        });

        // Botões "aba anterior / próxima" no rodapé de cada aba
        $(document).on('click', '[data-ir-aba]', function () {
            $abas.find('a[href="#' + $(this).data('ir-aba') + '"]').tab('show');
            var topo = $abas.offset().top - 80;
            if ($(window).scrollTop() > topo) { $('html, body').animate({ scrollTop: topo }, 150); }
        });
    }

    // Fecha os alertas automaticamente
    setTimeout(function () { $('.reurb-alerta.alert-success').alert('close'); }, 5000);
})(jQuery);
