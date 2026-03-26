<?php
/**
 * Admin Reports Page — Relatórios Administrativos
 * Comprehensive monitoring of credit usage and consultations across all users.
 * Data source: serc_consulta CPT + user meta
 */
if (!defined('ABSPATH'))
    exit;

if (!current_user_can('manage_options'))
    return;

require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';

// Get consultation type labels
$consultation_types = serc_get_consultation_types();
$integrations_config = serc_get_integrations_config();
?>

<div class="wrap serc-admin-reports">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <div class="serc-ar-header">
        <h1><span class="dashicons dashicons-chart-bar"></span> Relatórios</h1>
        <p class="serc-ar-subtitle">Monitoramento completo de consultas e uso de créditos</p>
    </div>

    <!-- ===================== FILTERS ===================== -->
    <div class="serc-ar-filters">
        <div class="serc-ar-filter-buttons">
            <button class="serc-ar-filter-btn active" data-period="today">Hoje</button>
            <button class="serc-ar-filter-btn" data-period="7days">Últimos 7 dias</button>
            <button class="serc-ar-filter-btn" data-period="30days">Últimos 30 dias</button>
            <button class="serc-ar-filter-btn" data-period="month">Este mês</button>
            <button class="serc-ar-filter-btn" data-period="custom">Personalizado</button>
        </div>
        <div class="serc-ar-custom-range" id="serc-ar-custom-range" style="display:none;">
            <input type="date" id="serc-ar-date-from">
            <span>até</span>
            <input type="date" id="serc-ar-date-to">
            <button class="button button-primary" id="serc-ar-apply-custom">Aplicar</button>
        </div>
    </div>

    <!-- =================== SECTION 1: GENERAL REPORT =================== -->
    <div class="serc-ar-section">
        <h2>Relatório Geral</h2>

        <div class="serc-ar-cards">
            <div class="serc-ar-card">
                <div class="serc-ar-card__icon" style="background: #e6f7ef;">
                    <span class="dashicons dashicons-search" style="color: #008F4C;"></span>
                </div>
                <div class="serc-ar-card__info">
                    <div class="serc-ar-card__value" id="ar-total-queries">—</div>
                    <div class="serc-ar-card__label">Consultas realizadas</div>
                </div>
            </div>
            <div class="serc-ar-card">
                <div class="serc-ar-card__icon" style="background: #fff3e0;">
                    <span class="dashicons dashicons-money-alt" style="color: #f59e0b;"></span>
                </div>
                <div class="serc-ar-card__info">
                    <div class="serc-ar-card__value" id="ar-total-credits">—</div>
                    <div class="serc-ar-card__label">Créditos consumidos</div>
                </div>
            </div>
            <div class="serc-ar-card">
                <div class="serc-ar-card__icon" style="background: #e8f4fd;">
                    <span class="dashicons dashicons-groups" style="color: #3b82f6;"></span>
                </div>
                <div class="serc-ar-card__info">
                    <div class="serc-ar-card__value" id="ar-total-users">—</div>
                    <div class="serc-ar-card__label">Usuários ativos</div>
                </div>
            </div>
            <div class="serc-ar-card">
                <div class="serc-ar-card__icon" style="background: #f3e8ff;">
                    <span class="dashicons dashicons-chart-line" style="color: #8b5cf6;"></span>
                </div>
                <div class="serc-ar-card__info">
                    <div class="serc-ar-card__value" id="ar-avg-queries">—</div>
                    <div class="serc-ar-card__label">Média por usuário</div>
                </div>
            </div>
        </div>

        <!-- Usage Timeline Chart -->
        <div class="serc-ar-chart-box">
            <h3>Consumo ao longo do tempo</h3>
            <div class="serc-ar-chart-container">
                <canvas id="ar-timeline-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- =================== SECTION 2: PER-USER REPORT =================== -->
    <div class="serc-ar-section">
        <h2>Relatório Individual por Usuário</h2>

        <div class="serc-ar-user-toolbar">
            <input type="text" id="ar-user-search" placeholder="Pesquisar por nome ou email..." class="regular-text">
            <button class="button" id="ar-export-csv">
                <span class="dashicons dashicons-download" style="vertical-align: middle;"></span> Exportar CSV
            </button>
        </div>

        <table class="wp-list-table widefat striped" id="ar-users-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Email</th>
                    <th>Créditos usados</th>
                    <th>Consultas</th>
                    <th>Última consulta</th>
                    <th>Saldo atual</th>
                </tr>
            </thead>
            <tbody id="ar-users-body">
                <tr>
                    <td colspan="6" style="text-align:center; padding:30px; color:#999;">Carregando dados...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- =================== SECTION 3: COMPLEMENTARY =================== -->
    <div class="serc-ar-section serc-ar-complementary">
        <div class="serc-ar-comp-grid">
            <!-- Ranking Chart -->
            <div class="serc-ar-comp-box">
                <h3>Consultas mais utilizadas</h3>
                <div class="serc-ar-chart-container" style="height:350px;">
                    <canvas id="ar-ranking-chart"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="serc-ar-comp-box">
                <h3>Atividade recente</h3>
                <div class="serc-ar-activity-list" id="ar-activity-list">
                    <div style="text-align:center; padding:30px; color:#999;">Carregando...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    var timelineChart = null;
    var rankingChart = null;
    var currentPeriod = 'today';

    function loadAdminReportData(period, dateFrom, dateTo) {
        var data = {
            action: 'serc_admin_report_data',
            nonce: '<?php echo wp_create_nonce('serc_admin_report'); ?>',
            period: period
        };
        if (period === 'custom') {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (!response.success || !response.data) return;
                var d = response.data;

                // Update cards
                $('#ar-total-queries').text(d.total_queries);
                $('#ar-total-credits').text(parseFloat(d.total_credits).toFixed(2));
                $('#ar-total-users').text(d.total_users);
                $('#ar-avg-queries').text(d.avg_queries);

                // Timeline chart
                renderTimelineChart(d.timeline_labels, d.timeline_data);

                // Users table
                renderUsersTable(d.users);

                // Ranking chart
                renderRankingChart(d.ranking_labels, d.ranking_data);

                // Recent activity
                renderActivityList(d.recent_activity);
            }
        });
    }

    function renderTimelineChart(labels, data) {
        var ctx = document.getElementById('ar-timeline-chart');
        if (!ctx) return;
        if (timelineChart) timelineChart.destroy();

        timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Consultas',
                    data: data,
                    borderColor: '#008F4C',
                    backgroundColor: 'rgba(0, 143, 76, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#008F4C'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        callbacks: {
                            label: function(ctx) { return ctx.raw + ' consultas'; }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false }, ticks: { maxRotation: 45 } }
                }
            }
        });
    }

    function renderRankingChart(labels, data) {
        var ctx = document.getElementById('ar-ranking-chart');
        if (!ctx) return;
        if (rankingChart) rankingChart.destroy();

        var colors = [
            '#008F4C', '#f59e0b', '#3b82f6', '#8b5cf6', '#e74c3c',
            '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#6366f1'
        ];

        rankingChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Consultas',
                    data: data,
                    backgroundColor: colors.slice(0, labels.length),
                    borderRadius: 6,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        callbacks: {
                            label: function(ctx) { return ctx.raw + ' consultas'; }
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    function renderUsersTable(users) {
        var tbody = $('#ar-users-body');
        tbody.empty();

        if (!users || users.length === 0) {
            tbody.html('<tr><td colspan="6" style="text-align:center; padding:30px; color:#999;">Nenhum dado encontrado para o período.</td></tr>');
            return;
        }

        users.forEach(function(u) {
            tbody.append(
                '<tr>' +
                '<td><strong>' + escHtml(u.name) + '</strong></td>' +
                '<td>' + escHtml(u.email) + '</td>' +
                '<td>' + parseFloat(u.credits_used).toFixed(2) + '</td>' +
                '<td>' + u.query_count + '</td>' +
                '<td>' + (u.last_query || '—') + '</td>' +
                '<td>' + parseFloat(u.balance).toFixed(2) + '</td>' +
                '</tr>'
            );
        });
    }

    function renderActivityList(activities) {
        var el = $('#ar-activity-list');
        el.empty();

        if (!activities || activities.length === 0) {
            el.html('<div style="text-align:center; padding:30px; color:#999;">Nenhuma atividade no período.</div>');
            return;
        }

        activities.forEach(function(a) {
            el.append(
                '<div class="serc-ar-activity-item">' +
                    '<div class="serc-ar-activity-icon"><span class="dashicons dashicons-search"></span></div>' +
                    '<div class="serc-ar-activity-info">' +
                        '<div class="serc-ar-activity-text"><strong>' + escHtml(a.user) + '</strong> realizou <em>' + escHtml(a.type) + '</em></div>' +
                        '<div class="serc-ar-activity-time">' + a.date + '</div>' +
                    '</div>' +
                '</div>'
            );
        });
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

    // Filter buttons
    $(document).on('click', '.serc-ar-filter-btn', function() {
        $('.serc-ar-filter-btn').removeClass('active');
        $(this).addClass('active');
        currentPeriod = $(this).data('period');

        if (currentPeriod === 'custom') {
            $('#serc-ar-custom-range').slideDown(200);
        } else {
            $('#serc-ar-custom-range').slideUp(200);
            loadAdminReportData(currentPeriod);
        }
    });

    // Custom range apply
    $('#serc-ar-apply-custom').on('click', function() {
        var from = $('#serc-ar-date-from').val();
        var to = $('#serc-ar-date-to').val();
        if (from && to) {
            loadAdminReportData('custom', from, to);
        }
    });

    // User search
    $('#ar-user-search').on('input', function() {
        var q = $(this).val().toLowerCase();
        $('#ar-users-body tr').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(q) !== -1);
        });
    });

    // Export CSV
    $('#ar-export-csv').on('click', function() {
        var rows = [['Usuário', 'Email', 'Créditos Usados', 'Consultas', 'Última Consulta', 'Saldo Atual']];
        $('#ar-users-body tr:visible').each(function() {
            var cols = [];
            $(this).find('td').each(function() {
                cols.push('"' + $(this).text().replace(/"/g, '""') + '"');
            });
            if (cols.length > 0) rows.push(cols);
        });

        var csv = rows.map(function(r) { return r.join(','); }).join('\n');
        var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'relatorio_usuarios_' + new Date().toISOString().slice(0, 10) + '.csv';
        link.click();
    });

    // Initial load
    loadAdminReportData('today');

})(jQuery);
</script>

<style>
/* ====================================
   Admin Reports Scoped Styles
   ==================================== */
.serc-admin-reports {
    max-width: 1200px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.serc-ar-header h1 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 24px;
    margin-bottom: 4px;
}

.serc-ar-subtitle {
    color: #666;
    font-size: 14px;
    margin-top: 0;
}

/* Filters */
.serc-ar-filters {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 20px;
}

.serc-ar-filter-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.serc-ar-filter-btn {
    padding: 6px 16px;
    background: #f0f0f1;
    color: #50575e;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s;
}

.serc-ar-filter-btn:hover {
    background: #e6f7ef;
    color: #008F4C;
    border-color: #008F4C;
}

.serc-ar-filter-btn.active {
    background: #008F4C;
    color: #fff;
    border-color: #008F4C;
}

.serc-ar-custom-range {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #eee;
}

.serc-ar-custom-range input[type="date"] {
    padding: 4px 8px;
}

/* Sections */
.serc-ar-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
}

.serc-ar-section h2 {
    font-size: 18px;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

/* Cards */
.serc-ar-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.serc-ar-card {
    background: #fafafa;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.serc-ar-card__icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.serc-ar-card__icon .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.serc-ar-card__value {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.2;
}

.serc-ar-card__label {
    font-size: 12px;
    color: #888;
    margin-top: 2px;
}

/* Chart */
.serc-ar-chart-box {
    margin-top: 10px;
}

.serc-ar-chart-box h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 12px 0;
}

.serc-ar-chart-container {
    height: 300px;
    position: relative;
}

/* User toolbar */
.serc-ar-user-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    gap: 12px;
}

.serc-ar-user-toolbar input {
    flex: 1;
    max-width: 350px;
}

/* Complementary grid */
.serc-ar-comp-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.serc-ar-comp-box {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
}

.serc-ar-comp-box h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 16px 0;
}

/* Activity list */
.serc-ar-activity-list {
    max-height: 400px;
    overflow-y: auto;
}

.serc-ar-activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
}

.serc-ar-activity-item:last-child {
    border-bottom: none;
}

.serc-ar-activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e6f7ef;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.serc-ar-activity-icon .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #008F4C;
}

.serc-ar-activity-text {
    font-size: 13px;
    line-height: 1.4;
}

.serc-ar-activity-time {
    font-size: 11px;
    color: #999;
    margin-top: 2px;
}

/* Responsive */
@media (max-width: 1024px) {
    .serc-ar-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    .serc-ar-comp-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .serc-ar-cards {
        grid-template-columns: 1fr;
    }
    .serc-ar-user-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
