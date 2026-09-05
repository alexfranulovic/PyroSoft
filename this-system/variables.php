<?php
if(!isset($seg)) exit;

/** DEBUG Mode to Devs **/
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);


/**
 * Inputs
 */
if (!defined('MIN_TIME_AUDIO'))        define('MIN_TIME_AUDIO', 5);       // seconds
if (!defined('MAX_TIME_AUDIO'))        define('MAX_TIME_AUDIO', 20);      // seconds
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


/**
 * Queue messages
 */
if (!defined('TIME_TO_DELETE_QUEUE_MESSAGES'))      define('TIME_TO_DELETE_QUEUE_MESSAGES', 30); // days
if (!defined('MAX_ATTEMPTS_TO_SEND_MESSAGES'))      define('MAX_ATTEMPTS_TO_SEND_MESSAGES', 3);
if (!defined('MESSAGE_SENDING_LIMIT_IN_THE_QUEUE')) define('MESSAGE_SENDING_LIMIT_IN_THE_QUEUE', 10);
