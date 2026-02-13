<?php
if (!isset($seg)) exit;


define('PYROSOFT_VERSION', '1.0.3');

/**
 * Define itens for CRON operations.
 */
define('REST_API_BASE_ROUTE', 'rest-api');


/**
 * Define itens for API operations.
 */
define('CRON_BASE_ROUTE', 'cron');


/**
 * Define "this system" paths.
 */
define('THIS_SYSTEM_PATH', "this-system/");
define('AREAS_PATH', THIS_SYSTEM_PATH ."areas");
define('AREAS_ABSOLUTE_PATH', __BASE_DIR__ . AREAS_PATH);


/**
 * Define plugins path.
 */
define('PLUGINS_PATH', 'this-system/plugins');
define('PLUGINS_ABSOLUTE_PATH', __BASE_DIR__ . PLUGINS_PATH);


/**
 * Define features path.
 */
define('FEATURES_PATH', 'ep-includes/features');
define('FEATURES_ABSOLUTE_PATH', __BASE_DIR__ . FEATURES_PATH);


/**
 * Define itens for Log operations.
 */
define('APP_LOG_DIR', __BASE_DIR__ . '/logs');
define('APP_LOG_FILE', APP_LOG_DIR . '/app.log');


/**
 * Inputs
 */
if (!defined('MIN_TIME_AUDIO'))        define('MIN_TIME_AUDIO', 1);       // seconds
if (!defined('MAX_TIME_AUDIO'))        define('MAX_TIME_AUDIO', 10);      // seconds
if (!defined('EVENT_DEFAULT_HOUR'))    define('EVENT_DEFAULT_HOUR', "09:00");


/**
 * Medias
 */
if (!defined('DEFAULT_FILES_VISIBILITY'))   define('DEFAULT_FILES_VISIBILITY', 'public');
if (!defined('MAX_MEDIA_ITEMS_IN_LIST'))    define('MAX_MEDIA_ITEMS_IN_LIST', 1);
if (!defined('DEFAULT_IMAGES_FOLDER'))      define('DEFAULT_IMAGES_FOLDER', 'pages');
if (!defined('TEMP_FILES_FOLDER'))          define('TEMP_FILES_FOLDER', 'uploads/temp/');
if (!defined('TIME_TO_DELETE_TEMP_FILES'))  define('TIME_TO_DELETE_TEMP_FILES', 30); // days


/**
 * User
 */
if (!defined('USER_PASSWORD_RECOVERY_TIME'))    define('USER_PASSWORD_RECOVERY_TIME', 3600);



global $alerts;
$alerts = [];

global $available_structured_data;
$available_structured_data = [];

global $all_input_types;
$all_input_types = [];

global $force_depth_zero_inputs;
$force_depth_zero_inputs = [];
$force_depth_zero_inputs[] = [
    'submit_button',
    'divider',
    'hidden'
];

global $inputs;
$inputs = [];

global $custom_pages_inputs;
$custom_pages_inputs = [];

global $custom_cruds_inputs;
$custom_cruds_inputs = [];

global $all_status;
$all_status = [];

global $login_forms;
$login_forms = [];

global $assets_to_load;
$assets_to_load = [
    'head' => [],
    'footer' => [],
];

global $user_status;
$user_status = [
    [
        'id'    => 1,
        'name'  => 'Ativo',
        'slug'  => 'enabled',
        'color' => 'subtle-success',
    ],
    [
        'id'    => 2,
        'name'  => 'Inativo',
        'slug'  => 'disabled',
        'color' => 'subtle-danger',
    ],
    [
        'id'    => 3,
        'name'  => 'Aguardando confirmação',
        'slug'  => 'review',
        'color' => 'subtle-warning',
    ],
    [
        'id'    => 4,
        'name'  => 'Spam',
        'slug'  => 'spam',
        'color' => 'subtle-primary',
    ],
];

global $general_status;
$general_status = [
    [
        'id'    => 1,
        'name'  => 'Ativo',
        'slug'  => 'enabled',
        'color' => 'success',
    ],
    [
        'id'    => 2,
        'name'  => 'Inativo',
        'slug'  => 'disabled',
        'color' => 'danger',
    ],
    [
        'id'    => 3,
        'name'  => 'Em análise',
        'slug'  => 'review',
        'color' => 'warning',
    ],
    [
        'id'    => 4,
        'name'  => 'Rascunho',
        'slug'  => 'draft',
        'color' => 'info',
    ],
];

global $custom_menu_type_options;
$custom_menu_type_options = [];

global $email_providers;
$email_providers = [];

global $html_classes;
$html_classes = [];

global $inputs_that_dont_need_name;
$inputs_that_dont_need_name = [];

global $login_social;
$login_social = [];

global $crud_action_triggers;
$crud_action_triggers = [
    'insert' => 'Cadastrar',
    'update' => 'Editar',
    'view' => 'Visualizar',
    'delete' => 'Deletar',
    'duplicate' => 'Duplicar',
    // 'truncate' => 'Truncar',
    // 'order' => 'Ordenar',
];

global $allowed_mime_types;
$allowed_mime_types =
[
    'images' => [
        'image/png',
        'image/jpeg',
        'image/pjpeg',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ],
    'videos' => [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/x-matroska',
        'video/quicktime',
        'video/x-msvideo',
    ],
    'audios' => [
        'audio/mpeg',
        'audio/ogg',
        'audio/wav',
        'audio/x-wav',
        'audio/webm',
        'audio/mp4',
    ],
    'archives' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-rar-compressed',
        'text/plain',
    ],
];


global $max_upload;

global $cron_schedules;
$cron_schedules = [];

global $tables;
$tables = [
    'tb_cron_events' => 'Eventos agendados',
    'tb_cruds' => 'CRUDs',
    'tb_info' => 'Informações do sistema',
    'tb_info' => 'Informações do sistema',
    'tb_cruds_fields' => 'Campos de CRUDs',
    'tb_menus' => 'Menus',
    'tb_pages' => 'Páginas',
    'tb_tokens' => 'Tokens',
    'tb_page_content' => 'Conteúdo de páginas',
    'tb_users' => 'Usuários',
    'tb_user_roles' => 'Funções de usuários',
    'tb_user_role_assignments' => 'Funções dos usuários',
    'tb_user_role_permissions' => 'Permissões de funções de usuários',
];
