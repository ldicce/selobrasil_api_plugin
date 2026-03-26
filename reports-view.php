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
    <div class="reports-header">
        <h2>
            <i class="ph-fill ph-chart-bar"></i>
            Relatórios de Uso
        </h2>
    </div>

    <!-- Filter Bar -->
    <div class="reports-filters">
        <div class="reports-filter-buttons">
            <button class="report-filter-btn active" data-period="day">Hoje</button>
            <button class="report-filter-btn" data-period="week">Esta semana</button>
            <button class="report-filter-btn" data-period="month">Este mês</button>
            <button class="report-filter-btn" data-period="custom">Personalizado</button>
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
                <i class="ph-bold ph-funnel"></i> Aplicar
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="reports-summary">
        <div class="report-summary-card">
            <div class="report-summary-icon report-icon-queries">
                <i class="ph-fill ph-magnifying-glass"></i>
            </div>
            <div class="report-summary-info">
                <div class="report-summary-value" id="report-total-queries">—</div>
                <div class="report-summary-label">Consultas realizadas</div>
            </div>
        </div>
        <div class="report-summary-card">
            <div class="report-summary-icon report-icon-credits">
                <i class="ph-fill ph-coins"></i>
            </div>
            <div class="report-summary-info">
                <div class="report-summary-value" id="report-total-credits">—</div>
                <div class="report-summary-label">Créditos consumidos</div>
            </div>
        </div>
        <div class="report-summary-card">
            <div class="report-summary-icon report-icon-balance">
                <i class="ph-fill ph-wallet"></i>
            </div>
            <div class="report-summary-info">
                <div class="report-summary-value">
                    <?php echo number_format(serc_get_user_credits(), 2, ',', '.'); ?>
                </div>
                <div class="report-summary-label">Saldo atual</div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="report-chart-wrapper">
        <h3>Consumo de créditos</h3>
        <div class="report-chart-container">
            <canvas id="creditUsageChart"></canvas>
        </div>
    </div>

    <!-- History Table -->
    <div class="report-history-wrapper">
        <h3>Histórico detalhado</h3>
        <div id="report-table-container">
            <table class="history-table" id="report-history-table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Créditos</th>
                    </tr>
                </thead>
                <tbody id="report-history-body">
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="ph-duotone ph-chart-bar"
                                style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                            Selecione um período para visualizar o relatório.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function($) {
    var creditChart = null;

    function initChart(labels, data) {
        var ctx = document.getElementById('creditUsageChart');
        if (!ctx) return;

        if (creditChart) {
            creditChart.destroy();
        }

        creditChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Créditos consumidos',
                    data: data,
                    backgroundColor: 'rgba(0, 143, 76, 0.2)',
                    borderColor: 'rgba(0, 143, 76, 1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    barPercentage: 0.6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        titleFont: { family: "'Poppins', sans-serif", size: 12 },
                        bodyFont: { family: "'Poppins', sans-serif", size: 12 },
                        callbacks: {
                            label: function(ctx) {
                                return 'Créditos: ' + parseFloat(ctx.raw).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(150, 150, 150, 0.15)' },
                        ticks: {
                            font: { family: "'Poppins', sans-serif", size: 11 },
                            callback: function(value) { return parseFloat(value).toFixed(2); }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: "'Poppins', sans-serif", size: 11 },
                            maxRotation: 45
                        }
                    }
                }
            }
        });
    }

    function loadReportData(period, dateFrom, dateTo) {
        var data = {
            action: 'serc_get_credit_report_data',
            nonce: serc_ajax.nonce,
            period: period
        };
        if (period === 'custom') {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }

        $.ajax({
            url: serc_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success && response.data) {
                    var rd = response.data;

                    // Update summary cards
                    $('#report-total-queries').text(rd.total_queries);
                    $('#report-total-credits').text(parseFloat(rd.total_credits).toFixed(2));

                    // Update chart
                    initChart(rd.chart_labels, rd.chart_data);

                    // Update table
                    var tbody = $('#report-history-body');
                    tbody.empty();

                    if (rd.activities && rd.activities.length > 0) {
                        rd.activities.forEach(function(act) {
                            var creditVal = act.credit_value ? ('-' + parseFloat(act.credit_value).toFixed(2)) : '—';
                            var creditClass = act.credit_value ? 'color: #e74c3c; font-weight: 600;' : 'color: var(--text-muted);';
                            var typeLabel = act.type === 'debit' ? 'Débito' : (act.type === 'query' ? 'Consulta' : act.type);
                            var typeBadge = act.type === 'debit'
                                ? '<span class="status-badge status-debit">' + typeLabel + '</span>'
                                : '<span class="status-badge status-query">' + typeLabel + '</span>';

                            tbody.append(
                                '<tr>' +
                                '<td>' +
                                    '<div style="font-weight:500;">' + act.date + '</div>' +
                                    '<div style="font-size:12px; color:var(--text-muted);">' + act.time + '</div>' +
                                '</td>' +
                                '<td>' + typeBadge + '</td>' +
                                '<td>' + act.description + '</td>' +
                                '<td style="' + creditClass + '">' + creditVal + '</td>' +
                                '</tr>'
                            );
                        });
                    } else {
                        tbody.html('<tr><td colspan="4" class="empty-state"><i class="ph-duotone ph-magnifying-glass" style="font-size:32px; margin-bottom:10px; display:block;"></i>Nenhuma atividade encontrada no período selecionado.</td></tr>');
                    }
                }
            },
            error: function() {
                $('#report-history-body').html('<tr><td colspan="4" class="empty-state">Erro ao carregar dados. Tente novamente.</td></tr>');
            }
        });
    }

    // Filter button clicks
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

    // Custom date range apply
    $(document).on('click', '#report-apply-custom', function() {
        var from = $('#report-date-from').val();
        var to = $('#report-date-to').val();
        if (from && to) {
            loadReportData('custom', from, to);
        }
    });

    // Auto-load today's data on page load
    // Use a small delay to ensure DOM is ready (especially for AJAX-loaded views)
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
