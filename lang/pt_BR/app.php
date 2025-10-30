<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Application Messages
    |--------------------------------------------------------------------------
    |
    | Mensagens específicas da aplicação Premia Plus
    |
    */

    // Perfil do usuário
    'profile' => [
        'retrieved' => 'Perfil recuperado com sucesso',
        'updated' => 'Perfil atualizado com sucesso',
        'no_changes' => 'Nenhuma alteração fornecida',
    ],

    // Senha
    'password' => [
        'changed' => 'Senha alterada com sucesso',
        'current_incorrect' => 'A senha atual está incorreta',
    ],

    // Rede/Network
    'network' => [
        'retrieved' => 'Rede recuperada com sucesso',
        'not_found' => 'Rede não encontrada',
    ],

    // Patrocinador
    'sponsor' => [
        'retrieved' => 'Patrocinador recuperado com sucesso',
        'not_found' => 'Você não possui patrocinador',
        'user_no_sponsor' => 'Usuário não possui patrocinador',
    ],

    // Estatísticas
    'statistics' => [
        'retrieved' => 'Estatísticas recuperadas com sucesso',
        'calculated' => 'Estatísticas calculadas com sucesso',
    ],

    // Usuário
    'user' => [
        'not_found' => 'Usuário não encontrado',
        'access_denied' => 'Acesso negado',
    ],

    // Operações gerais
    'operation' => [
        'success' => 'Operação realizada com sucesso',
        'completed' => 'Operação concluída',
        'failed' => 'Falha ao processar operação',
    ],

    // Validação específica do projeto
    'validation' => [
        'name_required' => 'O nome é obrigatório',
        'name_string' => 'O nome deve ser um texto válido',
        'name_max' => 'O nome não pode ter mais de :max caracteres',
        'phone_string' => 'O telefone deve ser um texto válido',
        'phone_max' => 'O telefone não pode ter mais de :max caracteres',
        'email_valid' => 'Informe um email válido',
        'email_unique' => 'Este email já está sendo usado',
        'username_unique' => 'Este nome de usuário já está sendo usado',
        'current_password_required' => 'A senha atual é obrigatória',
        'password_required' => 'A nova senha é obrigatória',
        'password_min' => 'A nova senha deve ter pelo menos :min caracteres',
        'password_confirmed' => 'A confirmação da senha não confere',
    ],

    // Logs e debugging
    'logs' => [
        'searching_user_network' => 'Buscando rede do usuário',
        'searching_sponsor' => 'Buscando patrocinador do usuário',
        'calculating_statistics' => 'Calculando estatísticas do usuário',
        'user_no_sponsor_warning' => 'Usuário não possui patrocinador',
    ],

    // Jobs específicos
    'jobs' => [
        'abandoned_cart' => [
            'starting' => 'Iniciando processamento de carrinhos abandonados...',
            'no_carts_found' => 'Nenhum carrinho abandonado encontrado',
            'carts_found' => 'Encontrados :count carrinhos abandonados para processar',
            'cart_marked_abandoned' => 'Carrinho :uuid marcado como abandonado',
            'processing_completed' => 'Processamento de carrinhos abandonados concluído',
            'failed' => 'Falha no job AbandonedCartJob: :error',
            'error_processing_cart' => 'Erro ao processar carrinho :uuid: :error',
            'recovery_email_sent' => 'Email de recuperação de carrinho deve ser enviado',
        ],
    ],

    // Autenticação
    'auth' => [
        'user_registered' => 'Usuário registrado com sucesso',
        'login_success' => 'Login realizado com sucesso',
        'logout_success' => 'Logout realizado com sucesso',
        'token_refreshed' => 'Token renovado com sucesso',
        'invalid_credentials' => 'As credenciais fornecidas estão incorretas',
        'account_disabled' => 'Conta desativada. Entre em contato com o suporte',
        'password_changed' => 'Senha alterada com sucesso',
        'current_password_incorrect' => 'A senha atual está incorreta',
    ],

    // Planos
    'plan' => [
        'listed' => 'Planos listados com sucesso',
        'found' => 'Plano encontrado com sucesso',
        'created' => 'Plano criado com sucesso',
        'updated' => 'Plano atualizado com sucesso',
        'deleted' => 'Plano deletado com sucesso',
        'activated' => 'Plano ativado com sucesso',
        'deactivated' => 'Plano desativado com sucesso',
        'promotional_listed' => 'Planos promocionais listados com sucesso',
        'search_completed' => 'Busca realizada com sucesso',
        'not_found' => 'Plano não encontrado',
        'not_found_or_inactive' => 'Plano não encontrado ou inativo',
        'error_listing' => 'Erro ao listar planos',
        'error_searching' => 'Erro ao buscar plano',
        'error_creating' => 'Erro ao criar plano',
        'error_updating' => 'Erro ao atualizar plano',
        'error_deleting' => 'Erro ao deletar plano',
        'error_promotional' => 'Erro ao listar planos promocionais',
    ],

    // Rifas e Tickets
    'raffle' => [
        'not_found' => 'Rifa não encontrada',
        'not_found_or_inactive' => 'Rifa não encontrada ou inativa',
        'tickets_applied' => 'Tickets aplicados com sucesso',
        'tickets_cancelled' => 'Tickets cancelados com sucesso',
        'tickets_partial_cancel' => 'Alguns tickets não puderam ser cancelados (já estão confirmados ou não pertencem a você)',
        'error_listing' => 'Erro ao listar rifas',
        'my_tickets_retrieved' => 'Meus tickets recuperados com sucesso',
    ],

    // API e Sistema
    'api' => [
        'working' => 'API Premia Plus funcionando!',
        'health_ok' => 'Sistema funcionando normalmente',
        'health_error' => 'Sistema com problemas',
    ],

    // Operações CRUD gerais
    'crud' => [
        'listed' => 'Dados listados com sucesso',
        'found' => 'Registro encontrado com sucesso',
        'created' => 'Registro criado com sucesso',
        'updated' => 'Registro atualizado com sucesso',
        'deleted' => 'Registro deletado com sucesso',
        'not_found' => 'Registro não encontrado',
        'error_listing' => 'Erro ao listar dados',
        'error_finding' => 'Erro ao buscar registro',
        'error_creating' => 'Erro ao criar registro',
        'error_updating' => 'Erro ao atualizar registro',
        'error_deleting' => 'Erro ao deletar registro',
    ],
];