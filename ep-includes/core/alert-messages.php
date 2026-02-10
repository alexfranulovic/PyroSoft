<?php
if(!isset($seg)) exit;

global $alerts;

/**
 * Manage error and success messages based on input values.
 *
 * This function takes an input value, checks it against predefined cases, and assigns an appropriate alert message to be returned.
 *
 * @param string $selector - The input value to determine the alert message.
 * @param array $view_mode - Select the mode how the message will be brought.
 *
 * @return array - An array containing the alert message details, including color, close button flag, and the message itself.
 */

$alerts = [
    'ER_TO_SCHEDULE' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O evento **NÃO FOI** agendado com sucesso, pois a data/hora estão **inválidas** ou já **passaram**.'
    ],
    'ER_TO_INSERT' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O registro não foi **CADASTRADO** com sucesso.'
    ],
    'ER_TO_UPDATE' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O registro não foi **ALTERADO** com sucesso.'
    ],
    'ER_TO_DUPLICATE' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O registro não foi **DUPLICADO** com sucesso.'
    ],
    'ER_TO_DELETE_A' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O registro não foi **APAGADO** com sucesso.'
    ],
    'ER_TO_DELETE_B' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O registro não foi **APAGADO** com sucesso. Existem registros cadastrados com ela.'
    ],
    'ER_TO_ORDER_A' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O registro não teve sua **ORDEM ALTERADA** com sucesso.'
    ],
    'ER_TO_ORDER_B' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Esta é a ordem **MÁXIMA** que o ítem poderá ter!'
    ],
    'ER_TO_TRUNCATE' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'A tabela não foi **LIMPA** com sucesso.'
    ],
    'ER_TO_IMPORT_BD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Base de dados não foi **IMPORTADA** com sucesso!'
    ],
    'ER_TO_EXPORT_BD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Base de dados não foi **RESTAURADA** com sucesso!'
    ],
    'ER_STATUS' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Alteração de status não foi executada com sucesso!'
    ],
    'ER_PLUGIN' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Não foi possível executar a ação para o plugin'
    ],
    'ER_TO_MANAGE_PERMISSION' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Não foi possível gerenciar a permissão',
    ],
    'ER_ADD_FAV_PROD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O produto não foi adicionado aos favoritos com sucesso!'
    ],
    'ER_DEL_FAV_PROD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O produto não foi removido dos favoritos com sucesso.'
    ],
    'ER_ADD_CART_PROD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O produto não foi adicionado ao carrinho com sucesso.'
    ],
    'ER_DEL_CART_PROD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O produto não foi removido do carrinho com sucesso.'
    ],
    'ER_SEND_EMAIL' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'O e-mail não foi **ENVIADO** com sucesso.'
    ],
    'ER_SEND_MSG' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Sua mensagem não foi enviada. Tente novamente mais tarde.'
    ],
    'ER_UNC_TRANSACTION' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'A transação não foi completada com sucesso.'
    ],
    'ER_UNC_COMPLAINT' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'A reclamação não foi aberta com sucesso.'
    ],
    'ER_UNC_CANCEL_TRANSACTION' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'A transação não foi cancelada com sucesso.'
    ],
    'ER_UNC_REVERSE_TRANSACTION' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'A transação não foi estornada com sucesso.'
    ],
    'ER_PASSWORD_LINK_INVALID' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Link inválido - tente recuperar a senha novamente.'
    ],
    'ER_PASSWORD_FIELDS_REQUIRED' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Necessário preencher o campos para editar a senha.'
    ],
    'ER_INVALID_PASSWORD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'A senha não cumpre os requisitos de seguraça.'
    ],
    'ER_INVALID_REPETITION_PASSWORD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'A nova senha e sua repetição não conferem.'
    ],
    'ER_PASSWORD_INVALID_CHARACTERES' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Caracter \' utilizado na senha é inválido.'
    ],
    'ER_NEW_PASSWORD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Erro ao alterar a senha.'
    ],
    'ER_INVALID_LOGIN' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Login ou senha inválidos. Tente novamente.'
    ],
    'ER_INVALID_PERMISSION' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Você não possui permissão para realizar essa ação.'
    ],
    'ER_RESTRICTED_AREA' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Área restrita para usuários cadastrados.'
    ],
    'ER_MAINTENANCE_PAGE' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Página em manutenção.'
    ],
    'ER_404_PAGE' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Página não encontrada.'
    ],
    'ER_ONLY_DEV_ALLOWED_TO_LOGIN' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Apenas desenvolvedores podem logar no momento.'
    ],
    'ER_DUPLICATED_NAME' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Já existe um registro com esse nome.'
    ],
    'ER_MAINTENANCE_PAGE' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Não existe uma conta com este e-mail.'
    ],
    'ER_UNDEFINED_ERROR' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Alguma falha do código foi encontrada. Contacte o desenvolvedor para solucioná-la.'
    ],
    'SC_TO_SCHEDULE' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Evento **AGENDADO** com sucesso para: '
    ],
    'SC_TO_INSERT' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O registro foi **CADASTRADO** com sucesso.'
    ],
    'SC_TO_UPDATE' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O registro foi **ALTERADO** com sucesso.'
    ],
    'SC_TO_DUPLICATE' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O registro foi **DUPLICADO** com sucesso.'
    ],
    'SC_TO_DELETE' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O registro foi **APAGADO** com sucesso.'
    ],
    'SC_TO_ORDER' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O registro teve sua **ORDEM ALTERADA** com sucesso.'
            ],
    'SC_TO_TRUNCATE' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'A tabela foi **LIMPA** com sucesso.'
    ],
    'SC_TO_IMPORT_BD' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Base de dados **IMPORTADA** com sucesso!'
    ],
    'SC_TO_EXPORT_BD' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Base de dados **RESTAURADA** com sucesso!'
    ],
    'SC_STATUS' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Alteração de status executada com sucesso!'
    ],
    'SC_PLUGIN' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Alteração para o plugin executada com sucesso.'
    ],
    'SC_TO_MANAGE_PERMISSION' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'A permissão foi gerenciada com sucesso!',
    ],
    'SC_ADD_FAV_PROD' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O produto foi adicionado aos favoritos.'
    ],
    'SC_DEL_FAV_PROD' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O produto foi removido dos favoritos.'
    ],
    'SC_ADD_CART_PROD' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O produto foi adicionado ao carrinho.'
    ],
    'SC_DEL_CART_PROD' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O produto foi removido do carrinho.'
    ],
    'SC_SEND_EMAIL' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'E-mail **ENVIADO** com sucesso.'
    ],
    'SC_SEND_MSG' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Sua mensagem foi enviada com sucesso! Entraremos em contato.'
    ],
    'SC_UNC_TRANSACTION' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Seu pedido foi realizado com sucesso! Em breve você receberá atualizações do pedido em seu e-mail.'
    ],
    'SC_UNC_COMPLAINT' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Reclamação aberta com sucesso.'
    ],
    'SC_UNC_CANCEL_TRANSACTION' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Transação foi cancelada com sucesso.'
    ],
    'SC_UNC_REVERSE_TRANSACTION' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Transação foi estornada com sucesso.'
    ],
    'SC_NEW_PASSWORD' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Senha alterada com sucesso! Redirecionando...'
    ],
    'SC_WELCOME_USER' => [
        'color' => 'success', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'Perfil cadastrado com sucesso. Bem vindo(a)!'
    ],
    'AL_INVALID_EXTENSION' => [
        'color' => 'warning', 'close_button' => true,
        'title' => '**Alerta!**',
        'body' => 'Extensão da imagem inválida! Tente novamente.'
    ],
    'AL_TO_INSERT' => [
        'color' => 'warning', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O registro foi **CADASTRADO** com sucesso, mas há alerta(s):'
    ],
    'AL_TO_UPDATE' => [
        'color' => 'warning', 'close_button' => true,
        'title' => 'Sucesso!',
        'body' => 'O registro foi **ALTERADO** com sucesso, mas há alerta(s):'
    ],
    'IF_USING_SANDBOX' => [
        'color' => 'info',
        'body' => 'Você está usando o Sandbox!',
    ],
    'IF_ONLY_LOGGED_USERS' => [
        'color' => 'info',
        'title' => 'Erro!',
        'body' => 'Você precisa estar logado para continuar. Faça login clicando <a href='. site_url('/login') .'>aqui</a>.'
    ],
    'IF_UNLOADED_FORM' => [
        'color' => 'info',
        'body' => 'Não foi possível renderizar o formulário/lista.'
    ],
    'IF_UNLOADED_LIST' => [
        'color' => 'info',
        'body' => 'Não foi possível renderizar a listagem.'
    ],
    'IF_NONEXISTENT_ID' => [
        'color' => 'info',
        'body' => 'Não existe nenhum registro com este ID.'
    ],
    'default' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => 'Alerta não identificado.'
    ]
];

function alert_message($alert, $view_mode = 'none')
{
    global $alerts;

    if (
        $view_mode != 'alert' &&
        $view_mode != 'toast' &&
        $view_mode != 'modal' &&
        $view_mode != 'none'
    ) { return 'View mode was not selected correctly.'; }

    if (is_array($alert))
    {
        return ($view_mode != 'none')
            ? block($view_mode, $alert)
            : $alert;
    }

    return ($view_mode != 'none')
        ? block($view_mode, $alerts[$alert] ?? $alerts['default'])
        : $alerts[$alert] ?? $alerts['default'];
}
