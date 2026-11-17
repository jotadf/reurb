/* Configurações comuns do Painel B3 */
window.Painel = (function () {
    var cores = {
        primary: '#4e73df', success: '#1cc88a', info: '#36b9cc', warning: '#f6c23e',
        danger: '#e74a3b', secondary: '#858796', dark: '#5a5c69'
    };
    var serie = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1', '#fd7e14', '#20c9a6'];

    var fmtNum = function (v, casas) {
        if (v === null || v === undefined || isNaN(v)) return '—';
        return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
    };

    if (window.Chart) {
        Chart.defaults.font.family = "Nunito, -apple-system, system-ui, 'Segoe UI', Roboto, Arial, sans-serif";
        Chart.defaults.color = '#858796';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.tooltip.backgroundColor = '#fff';
        Chart.defaults.plugins.tooltip.titleColor = '#6e707e';
        Chart.defaults.plugins.tooltip.bodyColor = '#858796';
        Chart.defaults.plugins.tooltip.borderColor = '#dddfeb';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.maintainAspectRatio = false;
        Chart.defaults.elements.point.radius = 0;
        Chart.defaults.elements.line.borderWidth = 2;
    }

    if ($ && $.fn && $.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            pageLength: 25,
            responsive: true,
            language: {
                decimal: ',', thousands: '.', emptyTable: 'Nenhum registro encontrado',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Mostrando 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)', lengthMenu: 'Exibir _MENU_ registros',
                loadingRecords: 'Carregando...', processing: 'Processando...', search: 'Pesquisar:',
                zeroRecords: 'Nenhum registro encontrado',
                paginate: { first: 'Primeiro', last: 'Último', next: 'Próximo', previous: 'Anterior' }
            }
        });
    }

    $(function () {
        // Máscaras
        if ($.fn.mask) {
            $('.mascara-cpf').mask('000.000.000-00');
        }
        // Botões que mostram "carregando" ao enviar
        $('form[data-carregando]').on('submit', function () {
            $(this).find('button[type=submit]').prop('disabled', true)
                .prepend('<span class="spinner-border spinner-border-sm mr-1" role="status"></span>');
        });
        $('a[data-carregando]').on('click', function () {
            $(this).addClass('disabled').prepend('<span class="spinner-border spinner-border-sm mr-1" role="status"></span>');
        });
    });

    return {
        cores: cores,
        serie: serie,
        num: fmtNum,
        moeda: function (v, casas) {
            if (v === null || v === undefined || isNaN(v)) return '—';
            return (v < 0 ? '-' : '') + 'R$ ' + fmtNum(Math.abs(v), casas === undefined ? 2 : casas);
        },
        // Eixo de datas padrão (pregões, sem fins de semana)
        eixoTempo: function (extra) {
            return $.extend(true, {
                type: 'timeseries',
                adapters: { date: { locale: 'pt-BR' } },
                time: { tooltipFormat: 'dd/MM/yyyy', displayFormats: { day: 'dd/MM', week: 'dd/MM', month: 'MMM/yy', quarter: 'MMM/yy', year: 'yyyy' } },
                grid: { display: false },
                ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }
            }, extra || {});
        },
        // Largura fixa do eixo Y para alinhar gráficos empilhados
        larguraEixo: function (eixo) { eixo.width = 64; },
        pct: function (v, casas) {
            if (v === null || v === undefined || isNaN(v)) return '—';
            return (v > 0 ? '+' : '') + fmtNum(v, casas === undefined ? 2 : casas) + '%';
        },
        abreviado: function (v) {
            if (v === null || v === undefined) return '—';
            var a = Math.abs(v);
            if (a >= 1e9) return fmtNum(v / 1e9, 2) + ' bi';
            if (a >= 1e6) return fmtNum(v / 1e6, 1) + ' mi';
            if (a >= 1e3) return fmtNum(v / 1e3, 1) + ' mil';
            return fmtNum(v, 0);
        }
    };
})();
