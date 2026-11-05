<?php
/**
 * Definição das telas de CRUD.
 *
 * As colunas são lidas do próprio banco (INFORMATION_SCHEMA), então:
 *  - colunas que existem no banco e não estão listadas aqui aparecem
 *    automaticamente no formulário, com rótulo gerado a partir do nome
 *    (ex.: as "42 more" de caracterizacao_vulnerabilidade_import);
 *  - colunas listadas aqui que não existem no banco são ignoradas.
 *
 * Opções de cada campo em 'campos':
 *   label        Rótulo exibido
 *   col          Largura na grade Bootstrap (1 a 12). Padrão: 6 (TEXT = 12)
 *   tipo         simnao | select | textarea | foto | senha | fk | ref | json
 *   opcoes       Para 'select': ['valor' => 'Texto', ...]
 *   fk           Para 'fk': ['tabela' => 'perfil', 'valor' => 'id', 'texto' => 'perfil']
 *   ref          Para 'ref': chave da tela de destino (gera botão "abrir")
 *   readonly     true = nunca editável (fica cinza, como na ficha)
 *   readonly_edicao  true = editável só na inclusão (ex.: ID do Kobo)
 *   obrigatorio  true = campo obrigatório
 *
 * Chaves da tela:
 *   tabela, titulo (menu/lista), ficha (título do formulário), icone (Font Awesome 5),
 *   pasta_fotos (subpasta das fotos dentro de 'fotos_base_url'),
 *   indicadores (colunas de situação na listagem: quantos registros de outra tela
 *     estão ligados a este; 'falta' = texto do selo quando não há nenhum;
 *     'mostrar' = 'quantidade' ou 'sim'; 'declarado' = coluna com a quantidade esperada),
 *     'acao_falta' = 'incluir' (atalho para cadastrar) ou 'corrigir' (tela Corrigir selo)
 *   corrigir_vinculo (tela de correção do vínculo, com sugestões; ver corrigir_vinculo.php)
 *   pk, lista (colunas da listagem), busca (colunas da pesquisa),
 *   ordem (ORDER BY padrão), filtros (colunas aceitas como filtro via URL),
 *   secoes (agrupamento visual), relacionados (registros ligados), somente_leitura
 */

$SIMNAO = ['tipo' => 'simnao'];

return [

    /* ============================================================== */
    /* EDIÇÃO DE DADOS                                                 */
    /* ============================================================== */

    'selagem' => [
        'tabela' => 'selagem_lotes_import',
        'pasta_fotos' => 'domicilio',    // mesmo formulário do Kobo que os domicílios: ~/www/arquivos/domicilio
        // Colunas de situação na listagem + filtro de pendências
        'indicadores' => [
            ['id' => 'sem_domicilio', 'titulo' => 'Domicílios', 'falta' => 'Sem domicílio',
             'tela' => 'domicilios', 'coluna' => 'id_submissao_pai', 'campo_local' => 'id_submissao',
             'mostrar' => 'quantidade', 'declarado' => 'qtd_domicilios_lote'],
        ],
        'titulo' => 'Selagens',
        'ficha'  => 'Ficha de Selagem de Lote',
        'icone'  => 'fa-id-card',
        'pk'     => 'id_submissao',
        'lista'  => ['id_submissao', 'rua_zona_setor', 'numero_lote', 'tipo_ocupacao_lote', 'qtd_domicilios_lote', 'nome_selador', 'data_formulario'],
        'busca'  => ['id_submissao', 'uuid', 'rua_zona_setor', 'numero_lote', 'endereco_oficial_completo', 'nome_selador'],
        'ordem'  => 'data_hora_submissao DESC',
        'campos' => [
            'id_submissao'              => ['label' => 'ID da Submissão (KoboToolbox)', 'readonly_edicao' => true, 'obrigatorio' => true],
            'uuid'                      => ['label' => 'UUID do Registro'],
            'rua_zona_setor'            => ['label' => 'Rua / Zona / Setor', 'col' => 8],
            'numero_lote'               => ['label' => 'Número do Lote', 'col' => 4],
            'endereco_oficial_completo' => ['label' => 'Endereço Oficial Completo', 'col' => 12],
            'tipo_ocupacao_lote'        => ['label' => 'Tipo de Ocupação do Lote', 'tipo' => 'select', 'opcoes' => [
                'Unifamiliar' => 'Unifamiliar', 'Multifamiliar' => 'Multifamiliar', 'Misto' => 'Misto',
                'Comercial' => 'Comercial', 'Institucional' => 'Institucional', 'Lote vazio' => 'Lote vazio',
            ]],
            'qtd_domicilios_lote'       => ['label' => 'Quantidade de Domicílios no Lote'],
            'nome_selador'              => ['label' => 'Nome do Selador'],
            'data_formulario'           => ['label' => 'Data de Coleta do Formulário'],
            'data_hora_submissao'       => ['label' => 'Data/Hora de Envio (Sincronismo)', 'readonly' => true],
            'versao'                    => ['label' => 'Versão do Formulário Kobo'],
            'qtd_domicilios_total'      => ['label' => 'Quantidade Total de Domicílios'],
            'comprovante_endereco'      => ['label' => 'Possui Comprovante de Endereço?', 'tipo' => 'simnao'],
            'foto_comprovante_endereco' => ['label' => 'Foto do Comprovante de Endereço', 'tipo' => 'foto', 'col' => 12],
            'observacoes'               => ['label' => 'Observações', 'col' => 12],
        ],
        'relacionados' => [
            ['chave' => 'domicilios', 'coluna' => 'id_submissao_pai', 'campo_local' => 'id_submissao', 'titulo' => 'Domicílios deste lote', 'bloqueia_exclusao' => true],
        ],
    ],

    'domicilios' => [
        'tabela' => 'domicilios_import',
        'pasta_fotos' => 'domicilio',    // ~/www/arquivos/domicilio
        // Colunas de situação na listagem + filtro de pendências
        'indicadores' => [
            ['id' => 'sem_socio', 'titulo' => 'Sócio jurídico', 'falta' => 'Falta',
             'tela' => 'socio', 'coluna' => 'codigo_selo', 'campo_local' => 'numero_selo', 'mostrar' => 'sim'],
            ['id' => 'sem_caracterizacao', 'titulo' => 'Caracterização', 'falta' => 'Falta',
             'tela' => 'caracterizacao', 'coluna' => 'codigo_selo', 'campo_local' => 'numero_selo', 'mostrar' => 'sim'],
        ],
        'titulo' => 'Domicílios',
        'ficha'  => 'Ficha de Domicílio',
        'icone'  => 'fa-home',
        'pk'     => 'numero_selo',
        'lista'  => ['numero_selo', 'id_submissao_pai', 'nome_principal_morador', 'cpf', 'telefone', 'tipo_ocupacao_imovel'],
        'busca'  => ['numero_selo', 'id_submissao_pai', 'nome_entrevistado', 'nome_principal_morador', 'cpf', 'telefone'],
        'ordem'  => 'numero_selo',
        'filtros'=> ['id_submissao_pai'],
        'secoes' => [
            'Identificação'          => ['numero_selo', 'id_submissao_pai', 'index_kobo'],
            'Moradores'              => ['nome_entrevistado', 'nome_principal_morador', 'telefone', 'cpf', 'casado_uniao_estavel', 'declaracao_ciencia', 'termo_lgpd'],
            'Imóvel'                 => ['uso_predominante', 'tipo_ocupacao_imovel', 'numero_pavimentos', 'localizacao_domicilio', 'acesso_independente', 'area_lote_m2'],
            'Comprovantes e fotos'   => ['comprovante_endereco', 'foto_comprovante_endereco', 'foto_fachada', 'foto_selo', 'foto_ocupacao'],
            'Localização (GPS)'      => ['latitude', 'longitude', 'altitude', 'precisao'],
        ],
        'campos' => [
            'numero_selo'               => ['label' => 'Número do Selo', 'readonly_edicao' => true, 'obrigatorio' => true],
            'id_submissao_pai'          => ['label' => 'ID da Selagem do Lote', 'tipo' => 'ref', 'ref' => 'selagem'],
            'index_kobo'                => ['label' => 'Índice no Kobo'],
            'nome_entrevistado'         => ['label' => 'Nome do Entrevistado'],
            'nome_principal_morador'    => ['label' => 'Nome do Principal Morador'],
            'telefone'                  => ['label' => 'Telefone'],
            'cpf'                       => ['label' => 'CPF'],
            'casado_uniao_estavel'      => ['label' => 'Casado / União Estável?', 'tipo' => 'simnao'],
            'declaracao_ciencia'        => ['label' => 'Declaração de Ciência'],
            'termo_lgpd'                => ['label' => 'Termo LGPD'],
            'uso_predominante'          => ['label' => 'Uso Predominante'],
            'tipo_ocupacao_imovel'      => ['label' => 'Tipo de Ocupação do Imóvel'],
            'numero_pavimentos'         => ['label' => 'Número de Pavimentos'],
            'localizacao_domicilio'     => ['label' => 'Localização do Domicílio no Lote'],
            'acesso_independente'       => ['label' => 'Acesso Independente?', 'tipo' => 'simnao'],
            'area_lote_m2'              => ['label' => 'Área do Lote (m²)'],
            'comprovante_endereco'      => ['label' => 'Possui Comprovante de Endereço?', 'tipo' => 'simnao'],
            'foto_comprovante_endereco' => ['label' => 'Foto do Comprovante de Endereço', 'tipo' => 'foto'],
            'foto_fachada'              => ['label' => 'Foto da Fachada', 'tipo' => 'foto'],
            'foto_selo'                 => ['label' => 'Foto do Selo', 'tipo' => 'foto'],
            'foto_ocupacao'             => ['label' => 'Foto da Ocupação', 'tipo' => 'foto'],
            'latitude'                  => ['label' => 'Latitude', 'col' => 3],
            'longitude'                 => ['label' => 'Longitude', 'col' => 3],
            'altitude'                  => ['label' => 'Altitude', 'col' => 3],
            'precisao'                  => ['label' => 'Precisão (m)', 'col' => 3],
        ],
        'relacionados' => [
            ['chave' => 'socio',          'coluna' => 'codigo_selo', 'campo_local' => 'numero_selo', 'titulo' => 'Cadastro sociojurídico deste domicílio', 'bloqueia_exclusao' => true],
            ['chave' => 'caracterizacao', 'coluna' => 'codigo_selo', 'campo_local' => 'numero_selo', 'titulo' => 'Caracterização deste domicílio', 'bloqueia_exclusao' => true],
        ],
    ],

    'socio' => [
        'tabela' => 'cadastro_sociojuridico_import',
        'pasta_fotos' => 'socio_juridico', // ~/www/arquivos/socio_juridico
        // Coluna "Domicílio" na listagem: o código do selo existe em Domicílios?
        'indicadores' => [
            ['id' => 'sem_domicilio', 'titulo' => 'Domicílio', 'falta' => 'Selo não encontrado',
             'tela' => 'domicilios', 'coluna' => 'numero_selo', 'campo_local' => 'codigo_selo',
             'mostrar' => 'sim', 'acao_falta' => 'corrigir'],
        ],
        // Tela "Corrigir selo": sugere domicílios parecidos com o selo gravado
        'corrigir_vinculo' => [
            'campo'   => 'codigo_selo',              // campo corrigido aqui
            'rotulo'  => 'selo',
            'tela'    => 'domicilios',               // onde o selo precisa existir
            'cpf'     => ['r1_cpf' => 'cpf'],        // mesmo CPF => sugestão forte
            'nome'    => ['r1_nome' => ['nome_principal_morador', 'nome_entrevistado']],
            'exibir'  => ['r1_nome', 'r1_cpf', 'nome_cadastrador', 'data_registro'],
        ],
        'titulo' => 'Sócio jurídicos',
        'ficha'  => 'Ficha de Cadastro Sociojurídico',
        'icone'  => 'fa-balance-scale',
        'pk'     => 'id_submissao',
        'lista'  => ['id_submissao', 'codigo_selo', 'r1_nome', 'r1_cpf', 'r1_telefone', 'nome_cadastrador', 'data_registro'],
        'busca'  => ['id_submissao', 'codigo_selo', 'uuid', 'r1_nome', 'r1_cpf', 'r1_rg', 'numero_nis', 'nome_cadastrador'],
        'ordem'  => 'data_registro DESC',
        'filtros'=> ['codigo_selo'],
        'secoes' => [
            'Identificação'               => ['id_submissao', 'codigo_selo', 'uuid', 'foto_selo'],
            'Responsável (Titular 1)'     => ['r1_nome', 'r1_cpf', 'r1_foto_cpf', 'r1_rg', 'r1_foto_rg', 'r1_naturalidade', 'r1_data_nascimento',
                                              'r1_estado_civil', 'foto_estado_civil', 'regime_bens_partes', 'r1_profissao', 'r1_escolaridade',
                                              'r1_telefone', 'r1_pcd', 'r1_especifiacao_pcd'],
            'Composição familiar e renda' => ['numero_residentes', 'renda_mensal_titular_1', 'renda_mensal_titular_2', 'renda_outras_fontes',
                                              'foto_comprovante_renda1', 'foto_comprovante_renda2', 'foto_comprovante_renda3',
                                              'cadunico_nis', 'numero_nis', 'recebe_beneficio_social', 'beneficios_detalhe'],
            'Imóvel e ocupação'           => ['relacao_com_imovel', 'forma_aquisicao', 'foto_comprovante_aquisicao', 'tempo_ocupacao', 'paga_iptu',
                                              'foto_comprovante_ocupacao_2022', 'foto_comprovante_ocupacao_2023', 'foto_comprovante_ocupacao_2024',
                                              'foto_comprovante_ocupacao_2025', 'foto_comprovante_ocupacao_2026'],
            'Declarações assinadas'       => ['assinou_unica_propriedade', 'foto_declaracao_unica_propriedade',
                                              'assinou_requerimento_regularizacao', 'foto_requerimento_regularizacao',
                                              'assinou_ocupacao_mansa_pacifica', 'foto_declaracao_ocupacao_mansa_pacifica',
                                              'assinou_veracidade', 'foto_declaracao_veracidade', 'assinou_lgpd', 'foto_declaracao_lgpd'],
            'Registro'                    => ['nome_cadastrador', 'data_registro'],
        ],
        'campos' => [
            'id_submissao'        => ['label' => 'ID da Submissão (KoboToolbox)', 'readonly_edicao' => true, 'obrigatorio' => true],
            'codigo_selo'         => ['label' => 'Código do Selo', 'tipo' => 'ref', 'ref' => 'domicilios'],
            'uuid'                => ['label' => 'UUID do Registro'],
            'foto_selo'           => ['label' => 'Foto do Selo', 'tipo' => 'foto'],
            'r1_nome'             => ['label' => 'Nome do Titular', 'col' => 12],
            'r1_cpf'              => ['label' => 'CPF'],
            'r1_foto_cpf'         => ['label' => 'Foto do CPF', 'tipo' => 'foto'],
            'r1_rg'               => ['label' => 'RG'],
            'r1_foto_rg'          => ['label' => 'Foto do RG', 'tipo' => 'foto'],
            'r1_naturalidade'     => ['label' => 'Naturalidade'],
            'r1_data_nascimento'  => ['label' => 'Data de Nascimento'],
            'r1_estado_civil'     => ['label' => 'Estado Civil'],
            'foto_estado_civil'   => ['label' => 'Foto do Comprovante de Estado Civil', 'tipo' => 'foto'],
            'regime_bens_partes'  => ['label' => 'Regime de Bens / Partes'],
            'r1_profissao'        => ['label' => 'Profissão'],
            'r1_escolaridade'     => ['label' => 'Escolaridade'],
            'r1_telefone'         => ['label' => 'Telefone'],
            'r1_pcd'              => ['label' => 'Pessoa com Deficiência?', 'tipo' => 'simnao'],
            'r1_especifiacao_pcd' => ['label' => 'Especificação da Deficiência'],
            'numero_residentes'   => ['label' => 'Número de Residentes'],
            'renda_mensal_titular_1' => ['label' => 'Renda Mensal Titular 1 (R$)'],
            'renda_mensal_titular_2' => ['label' => 'Renda Mensal Titular 2 (R$)'],
            'renda_outras_fontes'    => ['label' => 'Renda de Outras Fontes (R$)'],
            'foto_comprovante_renda1'=> ['label' => 'Comprovante de Renda 1', 'tipo' => 'foto'],
            'foto_comprovante_renda2'=> ['label' => 'Comprovante de Renda 2', 'tipo' => 'foto'],
            'foto_comprovante_renda3'=> ['label' => 'Comprovante de Renda 3', 'tipo' => 'foto'],
            'cadunico_nis'        => ['label' => 'Inscrito no CadÚnico?'],
            'numero_nis'          => ['label' => 'Número do NIS'],
            'recebe_beneficio_social' => ['label' => 'Recebe Benefício Social?', 'tipo' => 'simnao'],
            'beneficios_detalhe'  => ['label' => 'Benefícios Recebidos (detalhe)'],
            'relacao_com_imovel'  => ['label' => 'Relação com o Imóvel'],
            'forma_aquisicao'     => ['label' => 'Forma de Aquisição'],
            'foto_comprovante_aquisicao' => ['label' => 'Comprovante de Aquisição', 'tipo' => 'foto'],
            'tempo_ocupacao'      => ['label' => 'Tempo de Ocupação'],
            'paga_iptu'           => ['label' => 'Paga IPTU?', 'tipo' => 'simnao'],
            'foto_comprovante_ocupacao_2022' => ['label' => 'Comprovante de Ocupação 2022', 'tipo' => 'foto'],
            'foto_comprovante_ocupacao_2023' => ['label' => 'Comprovante de Ocupação 2023', 'tipo' => 'foto'],
            'foto_comprovante_ocupacao_2024' => ['label' => 'Comprovante de Ocupação 2024', 'tipo' => 'foto'],
            'foto_comprovante_ocupacao_2025' => ['label' => 'Comprovante de Ocupação 2025', 'tipo' => 'foto'],
            'foto_comprovante_ocupacao_2026' => ['label' => 'Comprovante de Ocupação 2026', 'tipo' => 'foto'],
            'assinou_unica_propriedade'          => ['label' => 'Assinou Declaração de Única Propriedade?', 'tipo' => 'simnao'],
            'foto_declaracao_unica_propriedade'  => ['label' => 'Declaração de Única Propriedade', 'tipo' => 'foto'],
            'assinou_requerimento_regularizacao' => ['label' => 'Assinou Requerimento de Regularização?', 'tipo' => 'simnao'],
            'foto_requerimento_regularizacao'    => ['label' => 'Requerimento de Regularização', 'tipo' => 'foto'],
            'assinou_ocupacao_mansa_pacifica'    => ['label' => 'Assinou Declaração de Ocupação Mansa e Pacífica?', 'tipo' => 'simnao'],
            'foto_declaracao_ocupacao_mansa_pacifica' => ['label' => 'Declaração de Ocupação Mansa e Pacífica', 'tipo' => 'foto'],
            'assinou_veracidade'          => ['label' => 'Assinou Declaração de Veracidade?', 'tipo' => 'simnao'],
            'foto_declaracao_veracidade'  => ['label' => 'Declaração de Veracidade', 'tipo' => 'foto'],
            'assinou_lgpd'                => ['label' => 'Assinou Termo LGPD?', 'tipo' => 'simnao'],
            'foto_declaracao_lgpd'        => ['label' => 'Termo LGPD', 'tipo' => 'foto'],
            'nome_cadastrador'            => ['label' => 'Nome do Cadastrador'],
            'data_registro'               => ['label' => 'Data do Registro'],
        ],
    ],

    'caracterizacao' => [
        'tabela' => 'caracterizacao_vulnerabilidade_import',
        'pasta_fotos' => 'caracterizacao', // ~/www/arquivos/caracterizacao
        // Coluna "Domicílio" na listagem: o código do selo existe em Domicílios?
        'indicadores' => [
            ['id' => 'sem_domicilio', 'titulo' => 'Domicílio', 'falta' => 'Selo não encontrado',
             'tela' => 'domicilios', 'coluna' => 'numero_selo', 'campo_local' => 'codigo_selo',
             'mostrar' => 'sim', 'acao_falta' => 'corrigir'],
        ],
        // Tela "Corrigir selo" (sem CPF/nome nesta tabela: sugere só por selo parecido)
        'corrigir_vinculo' => [
            'campo'   => 'codigo_selo',
            'rotulo'  => 'selo',
            'tela'    => 'domicilios',
            'exibir'  => ['responsavel_coleta', 'data_registro', 'quantidade_comodos'],
        ],
        'titulo' => 'Caracterizações',
        'ficha'  => 'Ficha de Caracterização de Vulnerabilidade',
        'icone'  => 'fa-building',
        'pk'     => 'id_submissao',
        'lista'  => ['id_submissao', 'codigo_selo', 'quantidade_comodos', 'possui_banheiro', 'esgotamento_sanitario', 'responsavel_coleta', 'data_registro'],
        'busca'  => ['id_submissao', 'codigo_selo', 'uuid', 'responsavel_coleta'],
        'ordem'  => 'data_registro DESC',
        'filtros'=> ['codigo_selo'],
        // Colunas do banco que não estiverem em nenhuma seção vão para "Outros dados".
        'secoes' => [
            'Identificação'          => ['id_submissao', 'codigo_selo', 'uuid', 'foto_selo'],
            'Cômodos e dormitórios'  => ['quantidade_comodos', 'comodos_improvisados_dormitorio', 'mais_de_3_por_dormitorio', 'faltam_camas',
                                         'ha_comodos_sem_janela', 'qtd_comodos_sem_janela'],
            'Banheiro'               => ['possui_banheiro', 'numero_banheiros', 'revestimento_ceramico_banheiro', 'louca_sanitaria_banheiro'],
            'Problemas observados'   => ['problemas_estruturais_observados', 'problemas_infiltracao', 'existe_comodo_com_mofo', 'comodos_com_mofo'],
            'Materiais e condições'  => ['paredes_materiais', 'paredes_condicao', 'cobertura_materiais', 'cobertura_condicao',
                                         'piso_materiais', 'piso_condicao', 'materiais_componencatorios_observados'],
            'Energia, água e esgoto' => ['energia_acesso', 'energia_condicao_instalacao', 'energia_condicao_instalacao_internas', 'agua_acesso',
                                         'instalacoes_hidrosanitarias_disponives', 'condicoes_hidrosanitarias',
                                         'esgotamento_sanitario', 'condicoes_esgotamento_sanitario'],
            'Pessoas com deficiência e saúde' => ['pcd_no_domicilio', 'quantidade_pcd', 'pessoas_mobilidade_reduzida', 'pessoas_cadeirantes',
                                         'ha_escada', 'ha_condicoes_mobilidade', 'pessoas_doencas_respiratorias',
                                         'quantidade_afetados_respiratorio', 'hove_acidentes_domesticos'],
            'Riscos: inundação e deslizamento' => ['ocorrencia_inundacao', 'frequencia_inundacao', 'altura_agua_inundacao', 'quando_chove',
                                         'imovel_terreno_tem_rachaduras', 'paredes_embarrigadas', 'ha_postes_arvores_inclinados',
                                         'houve_deslizamentos', 'quando_deslizamento', 'onde_deslizamento'],
            'Entorno e serviços urbanos' => ['coleta_lixo', 'frequencia_coleta_lixo', 'existe_drenagem', 'pavimentacao_rua', 'ha_calcada_na_rua',
                                         'possui_iluminacao_publica', 'sensacao_seguranca_rua',
                                         'possui_equipamentos_lazer', 'onde_equipamento_lazer',
                                         'possui_equipamentos_saude', 'onde_equipamento_saude',
                                         'possui_transporte_publico', 'onde_transporte_publico', 'principal_meio_locomocao'],
            'Portão e canteiro'      => ['posicao_portao_definitivo', 'possibilidade_cuidar_canteiro', 'tipo_plantas_canteiro'],
            'Registro'               => ['responsavel_coleta', 'data_registro', 'observacoes_gerais'],
        ],
        // Abas da ficha: cada aba reúne uma ou mais seções acima.
        'abas' => [
            'Moradia'         => ['Identificação', 'Cômodos e dormitórios', 'Banheiro'],
            'Construção'      => ['Problemas observados', 'Materiais e condições'],
            'Infraestrutura'  => ['Energia, água e esgoto'],
            'Pessoas e saúde' => ['Pessoas com deficiência e saúde'],
            'Riscos'          => ['Riscos: inundação e deslizamento'],
            'Entorno'         => ['Entorno e serviços urbanos', 'Portão e canteiro'],
            'Registro'        => ['Registro'],
        ],
        'campos' => [
            'id_submissao'                    => ['label' => 'ID da Submissão (KoboToolbox)', 'readonly_edicao' => true, 'obrigatorio' => true],
            'codigo_selo'                     => ['label' => 'Código do Selo', 'tipo' => 'ref', 'ref' => 'domicilios'],
            'uuid'                            => ['label' => 'UUID do Registro'],
            'foto_selo'                       => ['label' => 'Foto do Selo', 'tipo' => 'foto'],
            'quantidade_comodos'              => ['label' => 'Quantidade de Cômodos'],
            'comodos_improvisados_dormitorio' => ['label' => 'Cômodos Improvisados como Dormitório'],
            'mais_de_3_por_dormitorio'        => ['label' => 'Mais de 3 Pessoas por Dormitório'],
            'faltam_camas'                    => ['label' => 'Faltam Camas'],
            'possui_banheiro'                 => ['label' => 'Possui Banheiro?'],
            'numero_banheiros'                => ['label' => 'Número de Banheiros'],
            'revestimento_ceramico_banheiro'  => ['label' => 'Revestimento Cerâmico no Banheiro'],
            'louca_sanitaria_banheiro'        => ['label' => 'Louça Sanitária no Banheiro'],
            'problemas_estruturais_observados'=> ['label' => 'Problemas Estruturais Observados'],
            'problemas_infiltracao'           => ['label' => 'Problemas de Infiltração'],
            'existe_comodo_com_mofo'          => ['label' => 'Existe Cômodo com Mofo?'],
            'comodos_com_mofo'                => ['label' => 'Cômodos com Mofo'],
            'paredes_materiais'               => ['label' => 'Paredes – Materiais'],
            'paredes_condicao'                => ['label' => 'Paredes – Condição'],
            'cobertura_materiais'             => ['label' => 'Cobertura – Materiais'],
            'cobertura_condicao'              => ['label' => 'Cobertura – Condição'],
            'piso_materiais'                  => ['label' => 'Piso – Materiais'],
            'piso_condicao'                   => ['label' => 'Piso – Condição'],
            'materiais_componencatorios_observados' => ['label' => 'Materiais Compensatórios Observados', 'col' => 12],
            'energia_acesso'                  => ['label' => 'Acesso à Energia'],
            'energia_condicao_instalacao'     => ['label' => 'Condição da Instalação Elétrica'],
            'energia_condicao_instalacao_internas' => ['label' => 'Condição das Instalações Elétricas Internas'],
            'agua_acesso'                     => ['label' => 'Acesso à Água'],
            'instalacoes_hidrosanitarias_disponives' => ['label' => 'Instalações Hidrossanitárias Disponíveis'],
            'condicoes_hidrosanitarias'       => ['label' => 'Condições Hidrossanitárias'],
            'esgotamento_sanitario'           => ['label' => 'Esgotamento Sanitário'],
            'condicoes_esgotamento_sanitario' => ['label' => 'Condições do Esgotamento Sanitário'],
            'ha_comodos_sem_janela'           => ['label' => 'Há Cômodos sem Janela?'],
            'qtd_comodos_sem_janela'          => ['label' => 'Quantidade de Cômodos sem Janela'],
            'pcd_no_domicilio'                => ['label' => 'Há Pessoa com Deficiência no Domicílio?'],
            'quantidade_pcd'                  => ['label' => 'Quantidade de Pessoas com Deficiência'],
            'pessoas_mobilidade_reduzida'     => ['label' => 'Pessoas com Mobilidade Reduzida'],
            'pessoas_cadeirantes'             => ['label' => 'Pessoas Cadeirantes'],
            'ha_escada'                       => ['label' => 'Há Escada?'],
            'ha_condicoes_mobilidade'         => ['label' => 'Há Condições de Mobilidade?'],
            'pessoas_doencas_respiratorias'   => ['label' => 'Pessoas com Doenças Respiratórias'],
            'quantidade_afetados_respiratorio'=> ['label' => 'Quantidade de Afetados (Respiratório)'],
            'hove_acidentes_domesticos'       => ['label' => 'Houve Acidentes Domésticos?', 'col' => 12],
            'ocorrencia_inundacao'            => ['label' => 'Ocorrência de Inundação'],
            'frequencia_inundacao'            => ['label' => 'Frequência da Inundação'],
            'altura_agua_inundacao'           => ['label' => 'Altura da Água na Inundação'],
            'quando_chove'                    => ['label' => 'Quando Chove'],
            'imovel_terreno_tem_rachaduras'   => ['label' => 'Imóvel/Terreno tem Rachaduras?'],
            'paredes_embarrigadas'            => ['label' => 'Paredes Embarrigadas?'],
            'ha_postes_arvores_inclinados'    => ['label' => 'Há Postes ou Árvores Inclinados?'],
            'houve_deslizamentos'             => ['label' => 'Houve Deslizamentos?'],
            'quando_deslizamento'             => ['label' => 'Quando Ocorreu o Deslizamento'],
            'onde_deslizamento'               => ['label' => 'Onde Ocorreu o Deslizamento', 'col' => 12],
            'coleta_lixo'                     => ['label' => 'Coleta de Lixo'],
            'frequencia_coleta_lixo'          => ['label' => 'Frequência da Coleta de Lixo'],
            'existe_drenagem'                 => ['label' => 'Existe Drenagem?'],
            'pavimentacao_rua'                => ['label' => 'Pavimentação da Rua'],
            'ha_calcada_na_rua'               => ['label' => 'Há Calçada na Rua?'],
            'possui_iluminacao_publica'       => ['label' => 'Possui Iluminação Pública?'],
            'sensacao_seguranca_rua'          => ['label' => 'Sensação de Segurança na Rua (nota)'],
            'possui_equipamentos_lazer'       => ['label' => 'Possui Equipamentos de Lazer?'],
            'onde_equipamento_lazer'          => ['label' => 'Onde Fica o Equipamento de Lazer'],
            'possui_equipamentos_saude'       => ['label' => 'Possui Equipamentos de Saúde?'],
            'onde_equipamento_saude'          => ['label' => 'Onde Fica o Equipamento de Saúde'],
            'possui_transporte_publico'       => ['label' => 'Possui Transporte Público?'],
            'onde_transporte_publico'         => ['label' => 'Onde Fica o Transporte Público'],
            'principal_meio_locomocao'        => ['label' => 'Principal Meio de Locomoção'],
            'posicao_portao_definitivo'       => ['label' => 'Posição do Portão Definitivo'],
            'possibilidade_cuidar_canteiro'   => ['label' => 'Possibilidade de Cuidar de Canteiro'],
            'tipo_plantas_canteiro'           => ['label' => 'Tipo de Plantas para o Canteiro', 'col' => 12],
            'responsavel_coleta'              => ['label' => 'Responsável pela Coleta'],
            'data_registro'                   => ['label' => 'Data do Registro'],
            'observacoes_gerais'              => ['label' => 'Observações Gerais', 'col' => 12],
        ],
    ],

    /* ============================================================== */
    /* GESTÃO DE ACESSO                                                */
    /* ============================================================== */

    'perfil' => [
        'tabela' => 'perfil',
        'titulo' => 'Perfis',
        'ficha'  => 'Cadastro de Perfil',
        'icone'  => 'fa-id-card',
        'pk'     => 'id',
        'lista'  => ['id', 'perfil', 'descricao'],
        'busca'  => ['perfil', 'descricao'],
        'ordem'  => 'perfil',
        'campos' => [
            'id'        => ['label' => 'Código', 'col' => 3],
            'perfil'    => ['label' => 'Perfil', 'col' => 9, 'obrigatorio' => true],
            'descricao' => ['label' => 'Descrição', 'col' => 12],
        ],
    ],

    'equipe' => [
        'tabela' => 'equipe',
        'titulo' => 'Equipes',
        'ficha'  => 'Cadastro de Equipe',
        'icone'  => 'fa-users',
        'pk'     => 'id',
        'lista'  => ['id', 'equipe', 'descricao'],
        'busca'  => ['equipe', 'descricao'],
        'ordem'  => 'equipe',
        'campos' => [
            'id'        => ['label' => 'Código', 'col' => 3],
            'equipe'    => ['label' => 'Equipe', 'col' => 9, 'obrigatorio' => true],
            'descricao' => ['label' => 'Descrição', 'col' => 12],
        ],
    ],

    'usuario' => [
        'tabela' => 'usuario',
        'titulo' => 'Usuários',
        'ficha'  => 'Cadastro de Usuário',
        'icone'  => 'fa-user',
        'pk'     => 'id',
        'lista'  => ['id', 'nome', 'cpf', 'email', 'id_perfil', 'id_equipe', 'ativo'],
        'busca'  => ['nome', 'cpf', 'email'],
        'ordem'  => 'nome',
        'filtros'=> ['id_perfil', 'id_equipe', 'ativo'],
        'campos' => [
            'id'        => ['label' => 'Código', 'col' => 3],
            'nome'      => ['label' => 'Nome', 'col' => 9, 'obrigatorio' => true],
            'cpf'       => ['label' => 'CPF', 'obrigatorio' => true],
            'email'     => ['label' => 'E-mail', 'obrigatorio' => true],
            'senha'     => ['label' => 'Senha', 'tipo' => 'senha'],
            'ativo'     => ['label' => 'Situação', 'tipo' => 'select', 'opcoes' => ['1' => 'Ativo', '0' => 'Inativo'], 'obrigatorio' => true],
            'id_perfil' => ['label' => 'Perfil', 'tipo' => 'fk', 'fk' => ['tabela' => 'perfil', 'valor' => 'id', 'texto' => 'perfil'], 'obrigatorio' => true],
            'id_equipe' => ['label' => 'Equipe', 'tipo' => 'fk', 'fk' => ['tabela' => 'equipe', 'valor' => 'id', 'texto' => 'equipe']],
        ],
    ],

    'auditoria' => [
        'tabela' => 'auditoria',
        'titulo' => 'Auditoria',
        'ficha'  => 'Registro de Auditoria',
        'icone'  => 'fa-history',
        'pk'     => 'id',
        'somente_leitura' => true,
        'lista'  => ['id', 'atualizado', 'tabela', 'acao', 'usuario'],
        'busca'  => ['tabela', 'acao', 'valor_antigo', 'valor_atual'],
        'ordem'  => 'id DESC',
        'filtros'=> ['tabela', 'acao', 'usuario'],
        'campos' => [
            'id'           => ['label' => 'Código', 'col' => 3],
            'atualizado'   => ['label' => 'Data/Hora', 'col' => 3],
            'tabela'       => ['label' => 'Tabela', 'col' => 2],
            'acao'         => ['label' => 'Ação', 'col' => 2],
            'usuario'      => ['label' => 'Usuário', 'col' => 2, 'tipo' => 'fk', 'fk' => ['tabela' => 'usuario', 'valor' => 'id', 'texto' => 'nome']],
            'valor_antigo' => ['label' => 'Valor Anterior', 'tipo' => 'json'],
            'valor_atual'  => ['label' => 'Valor Atual', 'tipo' => 'json'],
        ],
    ],
];
