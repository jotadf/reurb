<?php
/**
 * Permissões por perfil (tabela perfil).
 *
 * A chave de cada perfil é o NOME cadastrado na tabela perfil (sem diferença de
 * maiúsculas ou acentos: "Usuário" = "usuario") ou o ID do perfil.
 *
 * Ações:  ver | incluir | alterar | excluir
 * Telas:  perfil, equipe, usuario, auditoria,
 *         selagem, domicilios, socio, caracterizacao
 *         '*' = todas as telas
 *
 * Perfil que não estiver aqui não acessa nenhuma tela.
 * A permissão é verificada em cada página, não só escondida no menu.
 */

$TUDO  = ['ver', 'incluir', 'alterar', 'excluir'];
$DADOS = ['selagem', 'domicilios', 'socio', 'caracterizacao'];

return [

    'perfis' => [
        // Pode alterar tudo, inclusive perfis, equipes, usuários e ver a auditoria
        'Administrador' => ['*' => $TUDO],

        // Pode incluir, alterar e excluir os dados da coleta.
        // Para não deixar excluir, troque $TUDO por ['ver', 'incluir', 'alterar'].
        'Usuário' => array_fill_keys($DADOS, $TUDO),

        // Só visualiza os dados da coleta
        'Observador' => array_fill_keys($DADOS, ['ver']),
    ],

    // Quem vê os itens do menu que apontam para telas já existentes no sistema.
    // Atenção: isto só esconde o link; a própria tela precisa fazer a verificação
    // (veja "Proteger telas existentes" no LEIAME.md).
    'menu_externo' => [
        'importar_dados'     => ['Administrador', 'Usuário'],
        'gerenciar_imagens'  => ['Administrador', 'Usuário'],
        'gerar_relatorio'    => ['Administrador', 'Usuário', 'Observador'],
        'selagens_sem_socio' => ['Administrador', 'Usuário', 'Observador'],
        'caract_sem_selagem' => ['Administrador', 'Usuário', 'Observador'],
    ],
];
