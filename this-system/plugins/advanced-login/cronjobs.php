<?php
if (!isset($seg)) exit;

/**
 * Advanced login - agenda a limpeza periodica:
 *  - remember tokens expirados (tb_user_remember_tokens)
 *  - tokens expirados do CMS (token_cleanup_expired)
 */
cron_schedule_event([
    'hook'       => 'advlogin_cleanup_cron',
    'slug'       => 'Advanced login cleanup',
    'timestamp'  => time() + 5,
    'recurrence' => 'hourly',
]);
