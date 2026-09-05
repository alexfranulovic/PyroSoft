<?php
if (!isset($seg)) exit;

/**
 * Advanced login - uninstall.
 *
 * Por seguranca, o DROP das tabelas fica COMENTADO: desativar o plugin nao
 * deve destruir vinculos sociais nem sessoes persistentes dos usuarios.
 * Descomente manualmente se realmente quiser zerar tudo.
 */

// query_it("DROP TABLE IF EXISTS `tb_user_remember_tokens`");
// query_it("DROP TABLE IF EXISTS `tb_user_social_identities`");

// Revoga tokens de magic link pendentes (esses sim sao efemeros).
if (function_exists('token_revoke_by_type_and_user')) {
    token_revoke_by_type_and_user(['type' => 'email_sso']);
}

query_it("DROP TABLE IF EXISTS tb_user_social_identities");
query_it("DROP TABLE IF EXISTS tb_user_remember_tokens");
