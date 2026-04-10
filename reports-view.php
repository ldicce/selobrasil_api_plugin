<?php
/**
 * View: Reports (Relatórios)
 * Credit usage reports with history table and consumption chart
 * Supports both full page load and AJAX partial loading
 */
if (!defined('ABSPATH'))
    exit;

// Require user to be logged in
if (!is_user_logged_in()) {
    wp_redirect('/login');
    exit;
}

// Check if this is an AJAX request
global $serc_ajax_request;
$is_ajax = !empty($serc_ajax_request);

$user_id = get_current_user_id();

// Only include header/sidebar for full page loads
if (!$is_ajax) {
    include plugin_dir_path(__FILE__) . 'includes/header.php';
    include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
    echo '<div class="area-content" style="grid-template-columns: 1fr;">';
}
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<div class="reports-container">

    <!-- ══════════════════════════════════════════
         SEÇÃO 1: Cabeçalho + Filtros
         ══════════════════════════════════════════ -->
    <div class="reports-page-header">
        <div class="reports-page-title">
            <h2>
                <i data-lucide="bar-chart-2"></i>
                Relatórios de Uso
            </h2>
            <p class="reports-page-subtitle">Análise detalhada de consumo de créditos e consultas realizadas</p>
        </div>
        <button class="reports-export-btn" id="reports-export-btn" title="Exportar dados">
            <i data-lucide="download"></i>
            Exportar Dados
        </button>
    </div>

    <!-- Painel de Filtros -->
    <div class="reports-filters-panel">
        <div class="reports-filters-panel-header" id="reports-filters-toggle">
            <div class="reports-filters-panel-title">
                <i data-lucide="sliders-horizontal"></i>
                <span>Filtros de Período</span>
            </div>
            <span class="reports-filters-panel-sub">Selecione o intervalo de tempo para análise</span>
            <i data-lucide="chevron-down" class="reports-filters-chevron" id="reports-filters-chevron"></i>
        </div>
        <div class="reports-filters-body" id="reports-filters-body">
            <div class="reports-filter-buttons">
                <button class="report-filter-btn active" data-period="day">
                    <i data-lucide="sun"></i> Hoje
                </button>
                <button class="report-filter-btn" data-period="week">
                    <i data-lucide="calendar-days"></i> Esta Semana
                </button>
                <button class="report-filter-btn" data-period="month">
                    <i data-lucide="calendar"></i> Este Mês
                </button>
                <button class="report-filter-btn" data-period="custom">
                    <i data-lucide="calendar-range"></i> Personalizado
                </button>
            </div>
            <div class="reports-custom-range" id="reports-custom-range" style="display:none;">
                <div class="filter-group">
                    <label>Data Início</label>
                    <input type="date" id="report-date-from" class="filter-input">
                </div>
                <div class="filter-group">
                    <label>Data Fim</label>
                    <input type="date" id="report-date-to" class="filter-input">
                </div>
                <button class="filter-btn" id="report-apply-custom">
                    <i data-lucide="filter"></i> Aplicar
                </button>
            </div>
            <!-- Active filter pill -->
            <div class="reports-active-filter-row">
                <div class="reports-active-filter-pill" id="reports-active-filter-pill">
                    <i data-lucide="sun"></i>
                    <span id="reports-active-period-label">Hoje</span>
                    <span class="reports-filter-pill-badge">Ativo</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         SEÇÃO 2: Gráfico + Painel de Métricas
         ══════════════════════════════════════════ -->
    <div class="reports-analytics-layout">

        <!-- Gráfico de Consumo -->
        <div class="reports-chart-section">
            <div class="reports-chart-section-header">
                <div>
                    <h3>
                        <i data-lucide="trending-up"></i>
                        Consumo de Créditos
                    </h3>
                    <p class="reports-chart-subtitle" id="reports-chart-subtitle">Consumo de créditos por período</p>
                </div>
            </div>
            <div class="reports-chart-container">
                <canvas id="creditUsageChart"></canvas>
            </div>
        </div>

        <!-- Painel de Métricas -->
        <div class="reports-metrics-panel">
            <div class="reports-metrics-panel-header">
                <i data-lucide="activity"></i>
                <div>
                    <h4>Resumo do Período</h4>
                    <span class="reports-metrics-live-badge">
                        <span class="reports-live-dot"></span>
                        Atualizado
                    </span>
                </div>
            </div>

            <div class="reports-metrics-list">

                <!-- Métrica: Consultas Realizadas -->
                <div class="reports-metric-row reports-metric-row--queries">
                    <div class="reports-metric-icon">
                        <i data-lucide="search"></i>
                    </div>
                    <div class="reports-metric-info">
                        <span class="reports-metric-label">Consultas Realizadas</span>
                        <span class="reports-metric-sub">Total no período</span>
                    </div>
                    <div class="reports-metric-value-wrap">
                        <span class="reports-metric-value" id="report-total-queries">—</span>
                        <span class="reports-metric-unit">consultas</span>
                    </div>
                </div>

                <!-- Métrica: Créditos Consumidos -->
                <div class="reports-metric-row reports-metric-row--credits">
                    <div class="reports-metric-icon">
                        <i data-lucide="coins"></i>
                    </div>
                    <div class="reports-metric-info">
                        <span class="reports-metric-label">Créditos Consumidos</span>
                        <span class="reports-metric-sub">Débito no período</span>
                    </div>
                    <div class="reports-metric-value-wrap">
                        <span class="reports-metric-value" id="report-total-credits">—</span>
                        <span class="reports-metric-unit">créditos</span>
                    </div>
                </div>

                <!-- Métrica: Saldo Atual -->
                <div class="reports-metric-row reports-metric-row--balance reports-metric-row--highlight">
                    <div class="reports-metric-icon">
                        <i data-lucide="wallet"></i>
                    </div>
                    <div class="reports-metric-info">
                        <span class="reports-metric-label">Saldo Atual</span>
                        <span class="reports-metric-sub">Disponível em conta</span>
                    </div>
                    <div class="reports-metric-value-wrap">
                        <span class="reports-metric-value">
                            <?php echo number_format(serc_get_user_credits(), 2, ',', '.'); ?>
                        </span>
                        <span class="reports-metric-unit">créditos</span>
                    </div>
                </div>

            </div>

            <!-- Footer do painel -->
            <div class="reports-metrics-footer">
                <div class="reports-metrics-footer-item">
                    <span class="reports-metrics-footer-label">Pico de Consultas</span>
                    <span class="reports-metrics-footer-value" id="reports-peak-queries">—</span>
                </div>
                <div class="reports-metrics-footer-item">
                    <span class="reports-metrics-footer-label">Maior Débito</span>
                    <span class="reports-metrics-footer-value" id="reports-peak-credits">—</span>
                </div>
            </div>
        </div>

    </div><!-- /.reports-analytics-layout -->

    <!-- ══════════════════════════════════════════
         SEÇÃO 3: Tabela de Histórico
         ══════════════════════════════════════════ -->
    <div class="reports-table-section">
        <div class="reports-table-section-header">
            <div>
                <h3>
                    <i data-lucide="list"></i>
                    Histórico Detalhado
                </h3>
                <p class="reports-table-subtitle">Registro completo de atividades no período</p>
            </div>
        </div>

        <div id="report-table-container">
            <table class="reports-history-table" id="report-history-table">
                <thead>
                    <tr>
                        <th><i data-lucide="clock"></i> Data / Hora</th>
                        <th><i data-lucide="tag"></i> Tipo</th>
                        <th><i data-lucide="file-text"></i> Descrição</th>
                        <th style="text-align:right;"><i data-lucide="coins"></i> Créditos</th>
                    </tr>
                </thead>
                <tbody id="report-history-body">
                    <tr>
                        <td colspan="4" class="reports-empty-state">
                            <i data-lucide="bar-chart-2"></i>
                            <span>Selecione um período para visualizar o relatório.</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer com totais -->
        <div class="reports-table-footer">
            <div class="reports-table-footer-item">
                <i data-lucide="search"></i>
                <div>
                    <span class="reports-table-footer-label">Total de Consultas</span>
                    <span class="reports-table-footer-value" id="footer-total-queries">—</span>
                </div>
            </div>
            <div class="reports-table-footer-item">
                <i data-lucide="coins"></i>
                <div>
                    <span class="reports-table-footer-label">Total de Créditos</span>
                    <span class="reports-table-footer-value" id="footer-total-credits">—</span>
                </div>
            </div>
            <div class="reports-table-footer-item">
                <i data-lucide="wallet"></i>
                <div>
                    <span class="reports-table-footer-label">Saldo Disponível</span>
                    <span class="reports-table-footer-value"><?php echo number_format(serc_get_user_credits(), 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div><!-- /.reports-table-section -->

</div><!-- /.reports-container -->

<script>
(function($) {
    var creditChart = null;

    /* ─── Period Labels ─── */
    var periodLabels = {
        'day':   'Hoje',
        'week':  'Esta Semana',
        'month': 'Este Mês',
        'custom':'Período Personalizado'
    };

    /* ─── Filter Panel Toggle ─── */
    $('#reports-filters-toggle').on('click', function() {
        var $body    = $('#reports-filters-body');
        var $chevron = $('#reports-filters-chevron');
        $body.slideToggle(200);
        $chevron.toggleClass('is-open');
    });

    /* ─── Chart Init ─── */
    function initChart(labels, data) {
        var ctx = document.getElementById('creditUsageChart');
        if (!ctx) return;

        if (creditChart) {
            creditChart.destroy();
        }

        creditChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Créditos consumidos',
                    data: data,
                    fill: true,
                    backgroundColor: function(context) {
                        var chart = context.chart;
                        var ctx2  = chart.ctx;
                        var chartArea = chart.chartArea;
                        if (!chartArea) return 'rgba(16, 185, 129, 0.08)';
                        var gradient = ctx2.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0,   'rgba(16, 185, 129, 0.22)');
                        gradient.addColorStop(1,   'rgba(16, 185, 129, 0.00)');
                        return gradient;
                    },
                    borderColor: '#10B981',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#10B981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        titleColor: '#94A3B8',
                        bodyColor: '#F1F5F9',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        padding: 12,
                        titleFont: { family: "'Inter', sans-serif", size: 11, weight: '500' },
                        bodyFont:  { family: "'Inter', sans-serif", size: 13, weight: '600' },
                        callbacks: {
                            label: function(ctx) {
                                return '  Créditos: ' + parseFloat(ctx.raw).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.12)',
                            drawBorder: false
                        },
                        ticks: {
                            font: { family: "'Inter', sans-serif", size: 11 },
                            color: '#94A3B8',
                            callback: function(value) { return parseFloat(value).toFixed(1); }
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: {
                            font: { family: "'Inter', sans-serif", size: 11 },
                            color: '#94A3B8',
                            maxRotation: 45
                        }
                    }
                }
            }
        });
    }

    /* ─── Load Report Data ─── */
    function loadReportData(period, dateFrom, dateTo) {
        var data = {
            action: 'serc_get_credit_report_data',
            nonce: serc_ajax.nonce,
            period: period
        };
        if (period === 'custom') {
            data.date_from = dateFrom;
            data.date_to   = dateTo;
        }

        // Update active filter pill
        var label = periodLabels[period] || 'Período';
        var icon  = (period === 'day') ? 'sun' : (period === 'week') ? 'calendar-days' : (period === 'month') ? 'calendar' : 'calendar-range';
        $('#reports-active-period-label').text(label);

        $.ajax({
            url:  serc_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success && response.data) {
                    var rd = response.data;

                    // Update main metric panel
                    $('#report-total-queries').text(rd.total_queries);
                    $('#report-total-credits').text(parseFloat(rd.total_credits).toFixed(2));

                    // Update footer totals
                    $('#footer-total-queries').text(rd.total_queries);
                    $('#footer-total-credits').text(parseFloat(rd.total_credits).toFixed(2));

                    // Update chart subtitle
                    $('#reports-chart-subtitle').text('Consumo de créditos — ' + label);

                    // Calculate peak values
                    if (rd.chart_data && rd.chart_data.length) {
                        var maxVal = Math.max.apply(null, rd.chart_data);
                        $('#reports-peak-credits').text(parseFloat(maxVal).toFixed(2));
                    }

                    // Update chart
                    initChart(rd.chart_labels, rd.chart_data);

                    // Update table
                    var tbody = $('#report-history-body');
                    tbody.empty();

                    if (rd.activities && rd.activities.length > 0) {

                        var queryPeak = 0;
                        rd.activities.forEach(function(act) {
                            queryPeak++;
                        });
                        $('#reports-peak-queries').text(rd.activities.length);

                        rd.activities.forEach(function(act) {
                            var creditVal   = act.credit_value ? parseFloat(act.credit_value).toFixed(2) : '0.00';
                            var displayVal  = act.credit_value ? ('-' + creditVal) : creditVal;
                            
                            var typeLabel = (act.type === 'debit') ? 'Atualização de Saldo' : 'Consulta Realizada';
                            var typeBadgeStyle = (act.type === 'debit') 
                                ? 'background:rgba(245, 158, 11, 0.1); color:#FCD34D; border:1px solid rgba(245, 158, 11, 0.2);' 
                                : 'background:var(--card-bg-elevated, rgba(255,255,255,0.05)); color:var(--text-color); border:1px solid var(--border-color, rgba(255,255,255,0.1));';

                            tbody.append(
                                '<tr>' +
                                '<td>' +
                                    '<div class="reports-table-date" style="font-weight:600; color:var(--text-color);">' + act.date + '</div>' +
                                    '<div class="reports-table-time" style="color:var(--text-muted);">' + act.time + '</div>' +
                                '</td>' +
                                '<td><span class="reports-type-badge" style="' + typeBadgeStyle + ' padding:4px 10px; border-radius:6px; font-size:11px;">' + typeLabel + '</span></td>' +
                                '<td class="reports-table-desc" style="color:var(--text-muted);">' + act.description + '</td>' +
                                '<td style="text-align:right; font-weight:600; color:var(--text-color);">' + displayVal + '</td>' +
                                '</tr>'
                            );
                        });

                        // Re-render Lucide icons in newly inserted rows (if any)
                        if (typeof lucide !== 'undefined') lucide.createIcons();

                    } else {
                        tbody.html(
                            '<tr><td colspan="4" class="reports-empty-state">' +
                            '<i data-lucide="search"></i>' +
                            '<span>Nenhuma atividade encontrada no período selecionado.</span>' +
                            '</td></tr>'
                        );
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                }
            },
            error: function() {
                $('#report-history-body').html(
                    '<tr><td colspan="4" class="reports-empty-state">' +
                    '<i data-lucide="alert-circle"></i>' +
                    '<span>Erro ao carregar dados. Tente novamente.</span>' +
                    '</td></tr>'
                );
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

    /* ─── Filter Button Clicks ─── */
    $(document).on('click', '.report-filter-btn', function() {
        $('.report-filter-btn').removeClass('active');
        $(this).addClass('active');

        var period = $(this).data('period');
        if (period === 'custom') {
            $('#reports-custom-range').slideDown(200);
        } else {
            $('#reports-custom-range').slideUp(200);
            loadReportData(period);
        }
    });

    /* ─── Custom Date Range Apply ─── */
    $(document).on('click', '#report-apply-custom', function() {
        var from = $('#report-date-from').val();
        var to   = $('#report-date-to').val();
        if (from && to) {
            loadReportData('custom', from, to);
        }
    });

    /* ─── Export Button ─── */
    $(document).on('click', '#reports-export-btn', function() {
        var $btn = $(this);

        // Determine current active period and custom dates
        var activePeriod = $('.report-filter-btn.active').data('period') || 'day';
        var dateFrom = '';
        var dateTo   = '';
        if (activePeriod === 'custom') {
            dateFrom = $('#report-date-from').val();
            dateTo   = $('#report-date-to').val();
            if (!dateFrom || !dateTo) {
                alert('Selecione o intervalo personalizado antes de exportar.');
                return;
            }
        }

        // Loading state
        $btn.prop('disabled', true).html('<i data-lucide="loader-2" class="lucide-spin"></i> Exportando...');
        if (window.lucide) lucide.createIcons();

        var requestData = {
            action: 'serc_get_credit_report_data',
            nonce: serc_ajax.nonce,
            period: activePeriod
        };
        if (activePeriod === 'custom') {
            requestData.date_from = dateFrom;
            requestData.date_to   = dateTo;
        }

        $.ajax({
            url:  serc_ajax.ajax_url,
            type: 'POST',
            data: requestData,
            success: function(response) {
                if (response.success && response.data && response.data.activities) {
                    var activities = response.data.activities;

                    // Build CSV rows
                    var csvRows = [
                        ['"Data"', '"Hora"', '"Tipo"', '"Descricao"', '"Creditos"']
                    ];

                    activities.forEach(function(act) {
                        var typeLabel = act.type === 'debit'  ? 'Debito'
                                      : act.type === 'query'  ? 'Consulta'
                                      : (act.type || '');
                        var credits = act.credit_value ? ('-' + parseFloat(act.credit_value).toFixed(2)) : '0.00';
                        var desc = (act.description || '').replace(/"/g, '""');

                        csvRows.push([
                            '"' + (act.date || '') + '"',
                            '"' + (act.time || '') + '"',
                            '"' + typeLabel + '"',
                            '"' + desc + '"',
                            '"' + credits + '"'
                        ]);
                    });

                    var csvContent = '\uFEFF' + csvRows.map(function(r) { return r.join(';'); }).join('\r\n');
                    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    var url  = URL.createObjectURL(blob);

                    // Period label for filename
                    var periodLabel = { day: 'hoje', week: 'semana', month: 'mes', custom: 'personalizado' }[activePeriod] || activePeriod;
                    var today = new Date();
                    var dateStr = today.getFullYear() + ('0'+(today.getMonth()+1)).slice(-2) + ('0'+today.getDate()).slice(-2);
                    var filename = 'relatorio_' + periodLabel + '_' + dateStr + '.csv';

                    var link = document.createElement('a');
                    link.setAttribute('href', url);
                    link.setAttribute('download', filename);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(function() { URL.revokeObjectURL(url); }, 1000);

                } else {
                    alert('Nenhum dado encontrado para exportar no período selecionado.');
                }
            },
            error: function() {
                alert('Erro ao exportar dados. Tente novamente.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i data-lucide="download"></i> Exportar Dados');
                if (window.lucide) lucide.createIcons();
            }
        });
    });

    /* ─── Auto-load Today on Page Load ─── */
    setTimeout(function() {
        if ($('.reports-container').length) {
            loadReportData('day');
        }
    }, 200);

})(jQuery);
</script>

<?php if (!$is_ajax): ?>
    </div>
    </div>
<?php endif; ?>
