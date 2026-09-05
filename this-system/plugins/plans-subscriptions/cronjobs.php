<?php
if (!isset($seg)) exit;


// dump( process_plan_subscriptions_cron());
// dump( subscription_payment_method_attempted_today(1, 65) );

cron_schedule_event([
  'hook'       => 'process_plan_subscriptions_cron',
  'slug'       => 'Process plan subscriptions cron',
  'timestamp'  => time() + 5,
  'recurrence' => 'every_minute',
]);
