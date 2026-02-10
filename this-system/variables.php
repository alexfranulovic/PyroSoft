<?php
if(!isset($seg)) exit;


/**
 * Inputs
 */
if (!defined('MIN_TIME_AUDIO'))        define('MIN_TIME_AUDIO', 1);       // seconds
if (!defined('MAX_TIME_AUDIO'))        define('MAX_TIME_AUDIO', 10);      // seconds
if (!defined('EVENT_DEFAULT_HOUR'))    define('EVENT_DEFAULT_HOUR', "10:00");


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

global $user_status;
$user_status[] =
[
    'id'    => 5,
    'name'  => 'Testde',
    'slug'  => 'asd',
    'color' => 'subtle-light',
];
