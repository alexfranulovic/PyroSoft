<?php
if(!isset($seg)) exit;


/** Load Plugin CRONs **/
load_plugins('cronjobs');

/** This system CRONs **/
load_this_system_functions('cronjobs');

/** Load Features CRONs **/
feature('all', 'cronjobs');


cron_schedule_event([
  'hook'       => 'clean_temp_uploads',
  'slug'       => 'Clean temp uploads',
  'timestamp'  => time() + 5,
  'recurrence' => 'daily',
]);


cron_schedule_event([
  'hook'       => 'clean_queue_messages',
  'slug'       => 'Clean queue messages',
  'timestamp'  => time() + 5,
  'recurrence' => 'daily',
]);


cron_schedule_event([
  'hook'       => 'token_cleanup_expired',
  'slug'       => 'Clean up expired tokens',
  'timestamp'  => time() + 5,
  'recurrence' => 'every_minute',
]);


cron_schedule_event([
  'hook'       => 'process_queue',
  'slug'       => 'Process queue messages',
  'timestamp'  => time() + 5,
  'recurrence' => 'everytime',
]);
