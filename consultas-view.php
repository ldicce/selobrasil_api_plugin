<?php
/**
 * View: Consultas (Specific Query Page)
 */
if (!defined('ABSPATH'))
    exit;

include plugin_dir_path(__FILE__) . 'includes/header.php';
include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="area-content">

    <style>
        /* Specific styles for Query View */
        .main-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            border: 1px solid #eee;
        }

        .main-card h2 {
            margin-top: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .main-card .subtitle {
            color: #888;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .step-container {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px dashed #eee;
        }

        .step-container:last-child {
            border-bottom: none;
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .step-num {
            background: #e6f7ef;
            color: var(--primary-green);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .step-title {
            font-weight: 600;
            color: #333;
        }

        .query-input-group {
            display: flex;
            gap: 12px;
        }

        .query-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: var(--font-main);
            background: #f9f9f9;
        }

        .btn-consultar {
            background: var(--primary-green);
            color: #fff;
            border: none;
            padding: 0 24px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-consultar:hover {
            background: #007a41;
        }

        .file-mockup {
            background: #f0f5f3;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .file-icon {
            color: var(--primary-green);
            font-size: 28px;
        }

        .file-details div {
            font-size: 14px;
            font-weight: 500;
            color: var(--primary-green);
        }

        .file-details small {
            font-size: 12px;
            color: #777;
        }

        .file-size {
            font-size: 12px;
            color: #888;
        }

        .result-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .person-info h3 {
            margin: 0;
            font-size: 18px;
        }

        .person-info p {
            margin: 4px 0;
            font-size: 13px;
            color: #666;
        }

        .score-circle {
            width: 100px;
            height: 100px;
            position: relative;
            border-radius: 50%;
            background: conic-gradient(var(--primary-green) 0% 75%, #eee 75% 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .score-circle::after {
            content: '';
            position: absolute;
            inset: 6px;
            background: #fff;
            border-radius: 50%;
        }

        .score-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .score-val {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-green);
            line-height: 1;
            display: block;
        }

        .score-label {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
            display: block;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #eee;
            border-radius: 6px;
            overflow: hidden;
        }

        .summary-table td {
            padding: 12px 16px;
            font-size: 13px;
            border-bottom: 1px solid #eee;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-table tr:nth-child(even) {
            background: #fafafa;
        }

        .price-col {
            text-align: right;
            font-weight: 500;
        }

        .btn-download {
            background: var(--primary-green);
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            float: right;
            margin-top: 20px;
        }

        .right-sidebar h3 {
            margin: 0 0 16px 0;
            font-size: 18px;
        }

        .category-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .tag {
            padding: 6px 12px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 4px;
            font-size: 12px;
            color: #555;
            cursor: pointer;
        }

        .tag.active {
            background: var(--primary-green);
            color: #fff;
            border-color: var(--primary-green);
        }

        .query-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .query-card {
            background: #fff;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .qc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .qc-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .qc-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }

        .qc-desc {
            font-size: 11px;
            color: #777;
            line-height: 1.4;
        }
    </style>

    <!-- Center Panel -->
    <div class="main-card">
        <h2>Consulta CPF</h2>
        <div class="subtitle">Informe o CPF que deseja consultar para gerar o relatório completo.</div>

        <!-- Steps... -->
        <div class="step-container">
            <div class="step-header">
                <div class="step-num">1</div>
                <div class="step-title">Digite o CPF</div>
            </div>
            <div class="query-input-group">
                <input type="text" class="query-input" placeholder="123.456.789-00">
                <button class="btn-consultar">Consultar</button>
            </div>
        </div>

        <div class="step-container">
            <div class="step-header">
                <div class="step-num">2</div>
                <div class="step-title">Seu relatório está pronto!</div>
            </div>
            <div class="file-mockup">
                <div class="file-info">
                    <i class="ph ph-folder-open file-icon"></i>
                    <div class="file-details">
                        <div>Baixar_relatório.pdf</div>
                        <small>Selo Brasil</small>
                    </div>
                </div>
                <div class="file-size">829 KB</div>
            </div>
        </div>

        <div class="step-container" style="border:none;">
            <div class="step-header">
                <div class="step-num">3</div>
                <div class="step-title">Informações do titular</div>
            </div>

            <div style="border:1px solid #eee; padding:20px; border-radius:8px;">
                <div class="result-section">
                    <div class="person-info">
                        <h3>José Roberto da Silva</h3>
                        <p>Nascimento: 03/05/1985</p>
                        <p>Mãe: Maria Aparecida dos Santos</p>
                    </div>
                    <div class="score-circle">
                        <div class="score-content">
                            <span class="score-val">762</span>
                            <span class="score-label">Muito bom</span>
                        </div>
                    </div>
                </div>

                <h4 style="margin:20px 0 10px 0; font-size:14px;">Resumo financeiro</h4>
                <table class="summary-table">
                    <tr>
                        <td>Dívidas</td>
                        <td class="price-col">R$ 3.870,25</td>
                        <td style="text-align:right; font-size:11px; color:#777;">2 Registros ▶</td>
                    </tr>
                    <tr>
                        <td>Inadimplências</td>
                        <td class="price-col">R$ 3.870,00</td>
                        <td style="text-align:right; font-size:11px; color:#777;">1 Protesto ▶</td>
                    </tr>
                </table>
            </div>

            <a href="#" class="btn-download">
                <i class="ph-bold ph-download-simple"></i> Baixar relatório
            </a>
            <div style="clear:both;"></div>
        </div>

    </div>

    <!-- Right Sidebar -->
    <div class="right-sidebar">
        <h3>Categorias</h3>
        <div class="category-tags">
            <span class="tag active">CPF</span>
            <span class="tag">CNPJ</span>
            <span class="tag">Veicular</span>
            <span class="tag">Jurídico</span>
        </div>

        <div class="query-list">
            <div class="query-card">
                <div class="qc-header">
                    <div class="qc-title"><i class="ph ph-magnifying-glass"></i> CPF Completo</div>
                    <button class="qc-btn">Consultar</button>
                </div>
                <div class="qc-desc">
                    Consulta completa do CPF com dados detalhados sobre o titular.
                </div>
            </div>
            <!-- Example Item -->
            <div class="query-card">
                <div class="qc-header">
                    <div class="qc-title"><i class="ph ph-magnifying-glass"></i> CPF Simples</div>
                    <button class="qc-btn">Consultar</button>
                </div>
                <div class="qc-desc">
                    Consulta básica cadastral para verificação de identidade.
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php wp_footer(); ?>
</body>

</html>