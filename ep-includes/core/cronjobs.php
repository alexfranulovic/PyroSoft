<?php
if(!isset($seg)) exit;


/** Load Plugin CRONs **/
load_plugins('cronjobs');

/** Load areas CRONs **/
load_areas_functions('cronjobs');

/** Load Features CRONs **/
feature('all', 'cronjobs');


cron_schedule_event([
  'hook'       => 'clean_temp_uploads',
  'slug'       => 'Clean temp uploads',
  'timestamp'  => time() + 5,
  'recurrence' => 'daily',
]);


cron_schedule_event([
  'hook'       => 'token_cleanup_expired',
  'slug'       => 'Clean up expired tokens',
  'timestamp'  => time() + 5,
  'recurrence' => 'every_minute',
]);
