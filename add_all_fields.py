#!/usr/bin/env python3
"""Quick script to add all missing fields to integrations"""

# Mapping of integration IDs to their field definitions
fields_map = {
    # CPF queries that need fields
    'srs_premium': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'scpc_boa_vista': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'bacen': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'quod': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'spc_brasil_cenprot': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'spc_brasil_serasa': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'dividas_bancarias': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'cadastrais_score_dividas': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'scr_bacen_score': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'ic_cnh': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    
    # CNPJ queries
    'bin_estadual': "[['name' => 'estado', 'label' => 'UF', 'placeholder' => 'SP', 'type' => 'text', 'required' => true]]",
    'bin_nacional': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    
    # Veicular queries  
    'ic_placa': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'crlv': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'renainf': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true], ['name' => 'renavam', 'label' => 'RENAVAM', 'placeholder' => '00000000000', 'type' => 'text', 'required' => true]]",
    'gravame': "[['name' => 'chassi', 'label' => 'Chassi', 'placeholder' => '17 caracteres', 'type' => 'text', 'required' => true]]",
    'laudo_veicular': "[['name' => 'placa', 'label' => 'Placa (opcional)', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => false], ['name' => 'chassi', 'label' => 'Chassi (opcional)', 'placeholder' => '17 caracteres', 'type' => 'text', 'required' => false]]",
    'proprietario_placa': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'foto_leilao': "[['name' => 'leilaoId', 'label' => 'ID do Leilão', 'placeholder' => '12345', 'type' => 'text', 'required' => true]]",
    'leilao': "[['name' => 'filtro', 'label' => 'Filtro', 'placeholder' => 'Filtro de busca', 'type' => 'text', 'required' => true]]",
    'leilao_score': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'historico_roubo': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'indice_risco': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'licenciamento_anterior': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'proprietario_atual': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'gravame_detalhamento': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'renajud': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'renainf_placa': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'fipe': "[['name' => 'marca', 'label' => 'Marca', 'placeholder' => 'Marca', 'type' => 'text', 'required' => true], ['name' => 'modelo', 'label' => 'Modelo', 'placeholder' => 'Modelo', 'type' => 'text', 'required' => true], ['name' => 'ano', 'label' => 'Ano', 'placeholder' => '2024', 'type' => 'text', 'required' => true]]",
    'sinistro': "[['name' => 'placa', 'label' => 'Placa', 'placeholder' => 'ABC1D23', 'type' => 'text', 'required' => true]]",
    'recall': "[['name' => 'modelo', 'label' => 'Modelo', 'placeholder' => 'Modelo do veículo', 'type' => 'text', 'required' => true]]",
    
    # Jurídico queries
    'dossie_juridico': "[['name' => 'document', 'label' => 'CPF ou CNPJ', 'placeholder' => 'Digite CPF ou CNPJ', 'type' => 'text', 'required' => true]]",
    'protesto_nacional': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'acoes_processos': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'dossie_juridico_cpf': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
    'certidao_trabalhista': "[['name' => 'cpf', 'label' => 'CPF', 'placeholder' => '000.000.000-00', 'type' => 'text', 'required' => true, 'class' => 'cpf']]",
}

print("// Add these 'fields' entries to the respective integrations:")
print()
for id, fields in fields_map.items():
    print(f"// {id}")
    print(f"'fields' => {fields},")
    print()
