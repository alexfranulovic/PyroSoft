<?php
if (!isset($seg)) exit;



/**
 * Builds a full URL for accessing cron routes.
 *
 * @param string $url Route suffix.
 * @return string Full cron route URL.
 */
function cron_route_url(string $url = ''): string
{
    return base_url . '/' . CRON_BASE_ROUTE . '/' . $url;
}


/**
 * Loads all available cron job hook definitions.
 *
 * @return void
 */
function load_crons_options(): void
{
    global $seg;
    include "ep-includes/core/cronjobs.php";
}


/**
 * Retrieves all scheduled cron events.
 *
 * @return array List of all cron events from the database.
 */
function all_cron_available(): array
{
    return get_results("SELECT * FROM tb_cron_events");
}


/**
 * Retrieves all cron events ready to be executed.
 *
 * @param int $now Current timestamp.
 * @return array List of ready cron events.
 */
function cron_get_ready_events(int $now): array
{
    return get_results("SELECT * FROM tb_cron_events WHERE timestamp <= {$now}");
}


/**
 * Executes all cron events that are ready based on current timestamp.
 *
 * @return void
 */
function cron_exec(): void
{
    global $seg;

    $now = time();
    $events = cron_get_ready_events($now);

    foreach ($events as $event)
    {
        $hook = $event['hook'];
        $mode = $event['mode'] ?? null;
        $args = $event['args'] ?? [];
        if (is_string($args)) {
            $dec = json_decode($args, true);
            if (json_last_error() === JSON_ERROR_NONE) $args = $dec;
        }
        $slug = $event['slug'];
        $id   = $event['id'];

        $recurrence = (int)($event['recurrence'] ?? 0) ?: null;

        if (function_exists($hook)) {
            $hook($args);
        }

        // Exclude after execution;
        query_it("DELETE FROM tb_cron_events WHERE id = '{$id}'");

        if (!empty($recurrence))
        {
            $next_exec = $now + $recurrence;

            cron_schedule_event([
                'slug'       => $slug,
                'hook'       => $hook,
                'mode'       => $mode,
                'timestamp'  => $next_exec,
                'recurrence' => $recurrence,
                'args'       => $args,
                'reschedule' => true,
            ]);
        }
    }

    load_crons_options();
}



/**
 * Schedules a new cron event.
 *
 * @param array $params {
 *     @type string $slug Event identifier.
 *     @type string $hook Callback function.
 *     @type string|null $mode Optional execution mode.
 *     @type int $timestamp Execution timestamp.
 *     @type int|null $recurrence Optional recurrence interval in seconds.
 *     @type array $args Optional arguments for the hook.
 * }
 * @return bool True if the event was scheduled successfully, false otherwise.
 */
function cron_schedule_event(array $params = []): bool
{
    global $cron_schedules;

    if (empty($params['hook']) || empty($params['slug'])) return false;

    $defaults = [
        'hook'       => '',
        'display'    => '',
        'timestamp'  => 0,
        'recurrence' => null,
        'args'       => [],
        'mode'       => null,
        'reschedule' => false,
    ];
    $params = array_merge($defaults, $params);
    extract($params);

    if (!$timestamp || $timestamp < time()) return false;

    $args_json = json_encode($args);

    $interval = null;
    if (is_string($recurrence) && isset($cron_schedules[$recurrence]['interval'])) {
        $interval = (int)$cron_schedules[$recurrence]['interval'];
    } elseif (is_numeric($recurrence) && (int)$recurrence > 0) {
        $interval = (int)$recurrence;
    }

    $now = time();
    $exists = get_col("
        SELECT COUNT(*) FROM tb_cron_events
        WHERE hook = '{$hook}'
          -- AND args = '{$args_json}'
          AND timestamp >= {$now}
        LIMIT 1
    ");
    // var_dump($exists);
    // var_dump("
    //     SELECT COUNT(*) FROM tb_cron_events
    //     WHERE hook = '{$hook}'
    //       -- AND args = '{$args_json}'
    //       AND timestamp >= {$now}
    //     LIMIT 1
    // ");
    if ($exists) return false;

    // Insert
    $payload = [
        'hook'       => $hook,
        'slug'       => $slug,
        'mode'       => $mode,
        'args'       => $args_json,
        'timestamp'  => $timestamp,
        'recurrence' => $interval ?: null,
    ];

    $insert = insert('tb_cron_events', $payload);
    // die;

    return ($insert->code == 'success');
}


/**
 * Finds the recurrence label based on the interval.
 *
 * @param int $interval Interval in seconds.
 * @return string|null Recurrence label or null if not found.
 */
function get_recurrence_key(int $interval): ?string
{
    global $cron_schedules;

    foreach ($cron_schedules as $key => $schedule) {
        if (isset($schedule['interval']) && $schedule['interval'] === $interval) {
            return $schedule['display'];
        }
    }

    return null;
}


/**
 * Formats next execution time and time until execution.
 *
 * @param int $timestamp Unix timestamp of the next execution.
 * @return string Formatted string with date and time left.
 */
function get_next_execution_info(int $timestamp): string
{
    global $config;

    // Create DateTime objects in the configured timezone
    $tz = new DateTimeZone("Etc/{$config['timezone']}");
    $dt  = (new DateTime('@' . $timestamp))->setTimezone($tz);
    $now = (new DateTime('now', $tz));

    // Formatted execution date string
    $formatted = $dt->format('d \d\e F \d\e Y H:i');

    // If the timestamp has already passed or is exactly now
    if ($dt->getTimestamp() <= $now->getTimestamp()) {
        return "{$formatted}<br>agora";
    }

    // Calculate difference with DateInterval
    $iv = $now->diff($dt);

    $until = [];

    // Simple pluralization helper
    $p = fn($n, $sg, $pl) => $n . ' ' . ($n == 1 ? $sg : $pl);

    // Years, months, days, hours, minutes
    if ($iv->y > 0)   $until[] = $p($iv->y, 'ano', 'anos');
    if ($iv->m > 0)   $until[] = $p($iv->m, 'mês', 'meses');
    if ($iv->d > 0)   $until[] = $p($iv->d, 'dia', 'dias');
    if ($iv->h > 0)   $until[] = $p($iv->h, 'hora', 'horas');
    if ($iv->i > 0)   $until[] = $p($iv->i, 'minuto', 'minutos');

    // Show seconds only if smaller than 1 minute
    if ($iv->y == 0 && $iv->m == 0 && $iv->d == 0 && $iv->h == 0 && $iv->i == 0 && $iv->s > 0) {
        $until[] = $p($iv->s, 'segundo', 'segundos');
    }

    // Safety fallback in case everything rounds to zero
    if (empty($until)) {
        $until[] = 'menos de 1 segundo';
    }

    return "{$formatted}<br>" . implode(' ', $until);
}



/**
 * Recurrence interval definitions.
 */
$cron_schedules =
[
    'every_minute' => [
        'interval' => 60,
        'display'  => 'Every minute',
    ],
    'every_2_minutes' => [
        'interval' => 60 * 2,
        'display'  => 'A cada 2 minutos',
    ],
    'every_5_minutes' => [
        'interval' => 60 * 5,
        'display'  => 'A cada 5 minutos',
    ],
    'every_15_minutes' => [
        'interval' => 60 * 15,
        'display'  => 'A cada 15 minutos',
    ],
    'every_30_minutes' => [
        'interval' => 60 * 30,
        'display'  => 'A cada 30 minutos',
    ],
    'hourly' => [
        'interval' => 60 * 60,
        'display'  => 'A cada hora',
    ],
    'twicedaily' => [
        'interval' => 60 * 60 * 12,
        'display'  => 'Duas vezes por dia',
    ],
    'daily' => [
        'interval' => 60 * 60 * 24,
        'display'  => 'Uma vez por dia',
    ],
    'weekly' => [
        'interval' => 60 * 60 * 24 * 7,
        'display'  => 'Uma vez por semana',
    ],
    'monthly' => [
        'interval' => 60 * 60 * 24 * 30,
        'display'  => 'Uma vez por mês',
    ],
    'bimonthly' => [
        'interval' => 60 * 60 * 24 * 60,
        'display'  => 'A cada 2 meses',
    ],
    'quarterly' => [
        'interval' => 60 * 60 * 24 * 90,
        'display'  => 'Trimestral',
    ],
    'semiannually' => [
        'interval' => 60 * 60 * 24 * 182,
        'display'  => 'Semestral',
    ],
    'yearly' => [
        'interval' => 60 * 60 * 24 * 365,
        'display'  => 'Anual',
    ],
    'biyearly' => [
        'interval' => 60 * 60 * 24 * 365 * 2,
        'display'  => 'Anual',
    ],
];

function debug_timestamp($timestamp)
{
    if (!is_numeric($timestamp)) {
        return "Invalid timestamp: " . var_export($timestamp, true);
    }
    return date("l, d/m/Y H:i:s T", $timestamp);
}
