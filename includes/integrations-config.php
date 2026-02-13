<?php
if (!defined('ABSPATH'))
    exit;

function serc_get_integrations_config()
{
    return [
        'cpf' => [
            ['id' => 'pf_dadosbasicos', 'name' => 'CPF Simples', 'description' => 'Consulta básica de CPF', 'shortcode' => 'serc_pf_dadosbasicos_form', 'value' => '5,50', 'type' => 'Requisição', 'icon' => 'ph-identification-card', 'fields' => [['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]],
            ['id' => 'ic_cpf_completo', 'name' => 'CPF Completo', 'description' => 'Consulta completa de CPF', 'shortcode' => 'serc_ic_cpf_completo_form', 'value' => '8,90', 'type' => 'Requisição', 'icon' => 'ph-identification-badge', 'fields' => [['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]],
            ['id' => 'r_cpf_completo', 'name' => 'CPF + Renda', 'description' => 'CPF completo com renda presumida', 'shortcode' => 'serc_r_cpf_completo_form', 'value' => '12,00', 'type' => 'Requisição', 'icon' => 'ph-currency-circle-dollar', 'fields' => [['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]],
            ['id' => 'ic_nome', 'name' => 'Busca por Nome', 'description' => 'Localizar dados por nome e UF', 'shortcode' => 'serc_ic_nome_form', 'value' => '7,50', 'type' => 'Requisição', 'icon' => 'ph-user-list', 'fields' => [['name' => 'name', 'label' => 'Nome Completo', 'placeholder' => 'Nome', 'type' => 'text', 'required' => true], ['name' => 'state', 'label' => 'UF', 'placeholder' => 'SP', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_telefone', 'name' => 'Busca por Telefone', 'description' => 'Localizar dados por telefone', 'shortcode' => 'serc_ic_telefone_form', 'value' => '6,90', 'type' => 'Requisição', 'icon' => 'ph-phone', 'fields' => [['name' => 'ddd', 'label' => 'DDD', 'placeholder' => '11', 'type' => 'text', 'required' => true, 'class' => 'ddd'], ['name' => 'phone', 'label' => 'Telefone', 'placeholder' => '999999999', 'type' => 'text', 'required' => true, 'class' => 'phone'], ['name' => 'state', 'label' => 'UF', 'placeholder' => 'SP', 'type' => 'text', 'required' => true]]],
        ],
        'cnpj' => [
            ['id' => 'cnpj', 'name' => 'CNPJ', 'description' => 'Consulta de CNPJ', 'shortcode' => 'serc_cnpj_form', 'value' => '10,00', 'type' => 'Requisição', 'icon' => 'ph-buildings', 'fields' => [['name' => 'cnpj', 'label' => 'CNPJ', 'placeholder' => '00.000.000/0000-00', 'type' => 'text', 'required' => true, 'class' => 'cnpj']]],
        ],
        'veicular' => [
            ['id' => 'agregados_propria', 'name' => 'Agregados', 'description' => 'Consulta de agregados veiculares', 'shortcode' => 'serc_agregados_propria_form', 'value' => '8,50', 'type' => 'Requisição', 'icon' => 'ph-car', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_bin_estadual', 'name' => 'BIN Estadual', 'description' => 'Base de Índice Nacional Estadual', 'shortcode' => 'serc_ic_bin_estadual_form', 'value' => '12,00', 'type' => 'Requisição', 'icon' => 'ph-map-pin', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_bin_nacional', 'name' => 'BIN Nacional', 'description' => 'Base de Índice Nacional Completa', 'shortcode' => 'serc_ic_bin_nacional_form', 'value' => '14,00', 'type' => 'Requisição', 'icon' => 'ph-globe', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_foto_leilao', 'name' => 'Foto Leilão', 'description' => 'Fotos de veículos em leilão', 'shortcode' => 'serc_ic_foto_leilao_form', 'value' => '5,00', 'type' => 'Requisição', 'icon' => 'ph-image', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'leilao', 'name' => 'Leilão', 'description' => 'Verificar passagem por leilão', 'shortcode' => 'serc_leilao_form', 'value' => '8,00', 'type' => 'Requisição', 'icon' => 'ph-gavel', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_leilao_score', 'name' => 'Leilão + Score', 'description' => 'Leilão com score e perda total', 'shortcode' => 'serc_ic_leilao_score_form', 'value' => '12,00', 'type' => 'Requisição', 'icon' => 'ph-chart-bar', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_laudo_veicular', 'name' => 'Laudo Veicular', 'description' => 'Laudo completo do veículo', 'shortcode' => 'serc_ic_laudo_veicular_form', 'value' => '25,00', 'type' => 'Requisição', 'icon' => 'ph-file-doc', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_historico_roubo_furto', 'name' => 'Histórico Roubo/Furto', 'description' => 'Histórico de ocorrências', 'shortcode' => 'serc_ic_historico_roubo_furto_form', 'value' => '11,00', 'type' => 'Requisição', 'icon' => 'ph-shield-warning', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'inde_risco', 'name' => 'Índice de Risco', 'description' => 'Análise de risco do veículo', 'shortcode' => 'serc_inde_risco_form', 'value' => '13,50', 'type' => 'Requisição', 'icon' => 'ph-warning-diamond', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_licenciamento_anterior', 'name' => 'Licenciamento Ant.', 'description' => 'Dados de licenciamento anterior', 'shortcode' => 'serc_ic_licenciamento_anterior_form', 'value' => '7,00', 'type' => 'Requisição', 'icon' => 'ph-calendar-check', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_proprietario_atual', 'name' => 'Proprietário Atual', 'description' => 'Dados do proprietário atual', 'shortcode' => 'serc_ic_proprietario_atual_form', 'value' => '15,00', 'type' => 'Requisição', 'icon' => 'ph-user-focus', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_recall', 'name' => 'Recall', 'description' => 'Verificar recalls pendentes', 'shortcode' => 'serc_ic_recall_form', 'value' => '4,50', 'type' => 'Requisição', 'icon' => 'ph-wrench', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_gravame', 'name' => 'Gravame Detalhado', 'description' => 'Informações detalhadas de gravame', 'shortcode' => 'serc_ic_gravame_form', 'value' => '16,00', 'type' => 'Requisição', 'icon' => 'ph-seal-check', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_renajud', 'name' => 'RENAJUD', 'description' => 'Restrições judiciais', 'shortcode' => 'serc_ic_renajud_form', 'value' => '10,00', 'type' => 'Requisição', 'icon' => 'ph-scales', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_renainf', 'name' => 'RENAINF', 'description' => 'Infrações de trânsito', 'shortcode' => 'serc_ic_renainf_form', 'value' => '9,50', 'type' => 'Requisição', 'icon' => 'ph-traffic-cone', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'fipe', 'name' => 'FIPE', 'description' => 'Valor de mercado FIPE', 'shortcode' => 'serc_fipe_form', 'value' => '3,00', 'type' => 'Requisição', 'icon' => 'ph-tag', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'sinistro', 'name' => 'Sinistro', 'description' => 'Histórico de sinistros', 'shortcode' => 'serc_sinistro_form', 'value' => '12,50', 'type' => 'Requisição', 'icon' => 'ph-first-aid', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'csv_completo', 'name' => 'CSV', 'description' => 'Certificado de Segurança Veicular', 'shortcode' => 'serc_csv_completo_form', 'value' => '20,00', 'type' => 'Requisição', 'icon' => 'ph-certificate', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'crlv', 'name' => 'CRLV', 'description' => 'Dados do documento CRLV', 'shortcode' => 'serc_crlv_form', 'value' => '12,00', 'type' => 'Requisição', 'icon' => 'ph-file-text', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
            ['id' => 'roubo_furto', 'name' => 'Roubo e Furto', 'description' => 'Checagem ativa de roubo/furto', 'shortcode' => 'serc_roubo_furto_form', 'value' => '11,00', 'type' => 'Requisição', 'icon' => 'ph-siren', 'fields' => [['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]],
        ],
        'credito' => [
            ['id' => 'cp_spc_cenprot', 'name' => 'SPC + Cenprot', 'description' => 'SPC com cartórios de protesto', 'shortcode' => 'serc_cp_spc_cenprot_form', 'value' => '13,00', 'type' => 'Requisição', 'icon' => 'ph-list-checks', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'r_spc_srs', 'name' => 'SPC + Serasa', 'description' => 'Consulta combinada SPC e Serasa', 'shortcode' => 'serc_r_spc_srs_form', 'value' => '16,00', 'type' => 'Requisição', 'icon' => 'ph-list-star', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'cp_serasa_premium_v2', 'name' => 'Serasa Premium v2', 'description' => 'Relatório completo Serasa (v2)', 'shortcode' => 'serc_cp_serasa_premium_v2_form', 'value' => '22,00', 'type' => 'Requisição', 'icon' => 'ph-star', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'cp_boa_vista_completa', 'name' => 'Cred Completa (BV)', 'description' => 'Consulta completa Boa Vista', 'shortcode' => 'serc_cp_boa_vista_completa_form', 'value' => '19,90', 'type' => 'Requisição', 'icon' => 'ph-shield-check', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'r_bv_basica', 'name' => 'Boa Vista Básica', 'description' => 'SCPC Boa Vista Básico', 'shortcode' => 'serc_r_bv_basica_form', 'value' => '12,50', 'type' => 'Requisição', 'icon' => 'ph-shield', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'cp_boa_vista_plus_v2', 'name' => 'Boa Vista Plus v2', 'description' => 'SCPC Boa Vista Plus (v2)', 'shortcode' => 'serc_cp_boa_vista_plus_v2_form', 'value' => '17,89', 'type' => 'Requisição', 'icon' => 'ph-shield-plus', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'cp_score_dividas', 'name' => 'Score + Dívidas', 'description' => 'Score de crédito e dívidas', 'shortcode' => 'serc_cp_score_dividas_form', 'value' => '18,50', 'type' => 'Requisição', 'icon' => 'ph-warning-circle', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'cp_cadastrais_score_dividas', 'name' => 'Cadastrais + Score + Dívidas', 'description' => 'Relatório de crédito completo', 'shortcode' => 'serc_cp_cadastrais_score_dividas_form', 'value' => '22,50', 'type' => 'Requisição', 'icon' => 'ph-user-circle-check', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_bacen', 'name' => 'SCR Bacen', 'description' => 'Sistema de Informações de Crédito', 'shortcode' => 'serc_ic_bacen_form', 'value' => '15,00', 'type' => 'Requisição', 'icon' => 'ph-bank', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'ac_protesto', 'name' => 'Protesto (CENPROT)', 'description' => 'Cartórios de Protesto Nacional', 'shortcode' => 'serc_ac_protesto_form', 'value' => '15,00', 'type' => 'Requisição', 'icon' => 'ph-gavel', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'ic_quod', 'name' => 'QUOD', 'description' => 'Bureau de crédito Quod', 'shortcode' => 'serc_ic_quod_form', 'value' => '14,50', 'type' => 'Requisição', 'icon' => 'ph-coin', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
        ],
        'juridico' => [
            ['id' => 'r_acoes_processos', 'name' => 'Ações e Processos', 'description' => 'Busca de processos judiciais', 'shortcode' => 'serc_r_acoes_processos_form', 'value' => '28,00', 'type' => 'Requisição', 'icon' => 'ph-gavel', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'dossie_juridico', 'name' => 'Dossiê Jurídico', 'description' => 'Levantamento jurídico completo', 'shortcode' => 'serc_dossie_juridico_form', 'value' => '35,00', 'type' => 'Requisição', 'icon' => 'ph-folder-open', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
            ['id' => 'cndt', 'name' => 'CNDT', 'description' => 'Certidão Nacional Débitos Trabalhistas', 'shortcode' => 'serc_cndt_form', 'value' => '18,00', 'type' => 'Requisição', 'icon' => 'ph-briefcase', 'fields' => [['name' => 'document', 'label' => 'CPF/CNPJ', 'placeholder' => 'CPF ou CNPJ', 'type' => 'text', 'required' => true]]],
        ]
    ];
}

function serc_get_category_integrations($category)
{
    $all = serc_get_integrations_config();
    return $all[$category] ?? [];
}

function serc_get_integration_by_id($integration_id)
{
    $all = serc_get_integrations_config();
    foreach ($all as $category => $integrations) {
        foreach ($integrations as $integration) {
            if ($integration['id'] === $integration_id) {
                return $integration;
            }
        }
    }
    return null;
}
