<?php
if (!isset($seg)) exit;


/**
 * Include the mandatory libraries and functions
 *
 * Developers must ensure that the following libraries and functions are included for the proper functioning of the system.
 * These files provide essential functionality required by the project.
 * 
 * >>> DON'T MOVE THEM!!! <<<
 */
require_once __DIR__ ."/logger-functions.php";
require_once __DIR__ ."/treatment-functions.php";
require_once __DIR__ ."/query-functions.php";
require_once __DIR__ ."/info-functions.php";
require_once __DIR__ ."/page-functions.php";
require_once __DIR__ ."/login-funcions.php";
require_once __DIR__ ."/permissions-functions.php";
require_once __DIR__ ."/user-functions.php";
require_once __DIR__ ."/formatting-string-functions.php";
require_once __DIR__ ."/general-functions.php";
require_once __DIR__ ."/crud-functions.php";
require_once __DIR__ ."/seo-functions.php";
require_once __DIR__ ."/rest-api-functions.php";
require_once __DIR__ ."/cron-functions.php";
require_once __DIR__ ."/token-functions.php";
require_once __DIR__ ."/loader-functions.php";
require_once __DIR__ ."/file-functions.php";
require_once __DIR__ ."/email-functions.php";
require_once __DIR__ ."/alert-messages.php";
require_once __DIR__ ."/form-input-functions.php";
require_once __DIR__ ."/theme-functions.php";
require_once __DIR__ ."/blocks.php";
