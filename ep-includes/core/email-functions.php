<?php
if(!isset($seg)) exit;

/**
 * Sends an email using a specified plugin and layout.
 *
 * @param array $args An associative array containing the email parameters.
 * Possible keys:
 *   - 'to': An array of recipient email addresses.
 *   - 'subject': The subject of the email.
 *   - 'body': The HTML content of the email (if no layout is used).
 *   - 'layout': The name of the layout function to use (optional).
 *   - 'plugin': The name of the plugin function to use for sending the email (optional).
 * @return mixed The result of the email sending operation or a message if the plugin is not installed.
 */
function send_email(array $args = [], $debug = false)
{
    global $config, $info;

    $email_config = $config['email_config'];

    $layout = $args['layout'] ?? 'email_default_layout';

    $body = function_exists($layout) ? $layout($args) : $args['body'];

    $payload = [
        'to' => $args['to'],
        'subject' => $args['subject'],
        'body' => $body,
    ];

    // Send the email with the plugin
    $plugin = $args['plugin'] ?? $email_config['plugin'];
    $plugin = "{$plugin}_send_email";

    if ($email_config['enable_sending'])
    {
        $res = function_exists($plugin)
            ? $plugin($payload)
            : "The selected plugin is not installed.";
    }

    if($debug) {
        echo $body;
        print_r($res);
    }

    return !$email_config['enable_sending']
        ? [
            'code' => 'success',
            'msg' => 'Sending e-mails is not enabled.'
        ]
        : $res;
}

/**
 * Resolves the absolute file path of an email template based on a logical template identifier.
 *
 * Supported template formats:
 * - `plugin/{plugin_slug}/{template_name}`
 * - `feature/{feature_slug}/{template_name}`
 * - `{template_name}` (defaults to `this-system/emails`)
 *
 * Resolution priority:
 * 1. `this-system/emails/{template_name}.php` override
 * 2. Plugin/feature email template file
 * 3. Default `this-system/emails/{template_name}.php`
 *
 * Examples:
 * - `plugin/pyrosales/order-confirmation`
 * - `feature/auth/reset-password`
 * - `welcome-email`
 *
 * @param string $template Logical template identifier.
 *
 * @return string Absolute template path when found, otherwise an empty string.
 */
function resolve_template_path(string $template): string
{
    $template = trim($template, '/');

    // plugin/x/template
    if (strpos($template, 'plugin/') === 0)
    {
        $parts = explode('/', $template, 3);

        if (count($parts) === 3)
        {
            $plugin = $parts[1];
            $file   = $parts[2];

            // prioridade: this-system sobrescreve plugin
            $override = __BASE_DIR__ . "/this-system/emails/{$file}.php";
            if (file_exists($override)) return $override;

            $plugin_path = __BASE_DIR__ . "/this-system/plugins/{$plugin}/emails/{$file}.php";
            if (file_exists($plugin_path)) return $plugin_path;
        }
    }

    // plugin/x/template
    elseif (strpos($template, 'feature/') === 0)
    {
        $parts = explode('/', $template, 3);

        if (count($parts) === 3)
        {
            $feature = $parts[1];
            $file    = $parts[2];

            // prioridade: this-system sobrescreve feature
            $override = __BASE_DIR__ . "/this-system/emails/{$file}.php";
            if (file_exists($override)) return $override;

            $feature_path = __BASE_DIR__ . "/ep-incldues/features/{$feature}/emails/{$file}.php";
            if (file_exists($feature_path)) return $feature_path;
        }
    }

    // this-system straight
    $default = __BASE_DIR__ . "/this-system/emails/{$template}.php";
    if (file_exists($default)) return $default;

    return '';
}

/**
 * Queue a message for async processing.
 *
 * @param array $message
 * @return array
 */
function queue_message(array $message): array
{
    $template        = $message['template'] ?? null;
    $template_params = $message['template_params'] ?? [];
    $body            = $message['body'] ?? null;

    // regra: se tem template + params → NÃO salva body
    if (!empty($template) && !empty($template_params)) {
        $body = null;
    }

    insert('tb_queue_messages', [
        'type'            => 'email',
        'provider'        => $message['provider'] ?? null,
        'to_data'         => json_encode($message['to'] ?? []),
        'subject'         => $message['subject'] ?? null,
        'template'        => $template,
        'template_params' => !empty($template_params) ? json_encode($template_params) : null,
        'message'         => $body,
        'status'          => 'pending',
        'attempts'        => 0,
        'created_at'      => date('Y-m-d H:i:s'),
    ]);

    return ['code' => 'success', 'msg' => ['reason' => 'queued']];
}

/**
 * Processes pending messages from the sending queue.
 *
 * This function:
 * - Fetches pending queue messages up to the configured limit
 * - Applies a lightweight lock per row to prevent multiple cron workers
 *   from processing the same message
 * - Builds the email payload from a template when `template` is defined
 * - Sends the email directly when a raw `message` body is available
 * - Updates the queue row as `sent`, `pending`, or `failed`
 *   depending on the result and number of attempts
 *
 * Locking strategy:
 * - Each message is atomically updated from `pending` to `locked`
 * - If the update affects 0 rows, another worker already claimed the message
 *
 * @param int|array $params Optional execution parameters.
 *
 * @return void
 */
function process_queue(int|array $params = []): void
{
    extract($params);

    $max_attempts = MAX_ATTEMPTS_TO_SEND_MESSAGES;
    $limit        = $limit ?? MESSAGE_SENDING_LIMIT_IN_THE_QUEUE;

    $messages = get_results("
        SELECT * FROM tb_queue_messages
        WHERE status = 'pending'
          AND attempts < {$max_attempts}
        ORDER BY id ASC
        LIMIT {$limit}
    ");

    foreach ($messages as $msg)
    {
        // LOCK seguro
        query_it("
            UPDATE tb_queue_messages
            SET status='locked'
            WHERE id={$msg['id']}
              AND status='pending'
            LIMIT 1
        ");

        if (affected_rows() === 0) {
            continue; // outro cron pegou
        }

        $res = ['code' => 'error'];

        $to     = $msg['to_data'];
        $params = $msg['template_params'] ?? [];

        if (!empty($msg['template']))
        {
            $built = build_email_message($msg['template'], [
                'template_params' => $params
            ]);

            if (($built['code'] ?? '') !== 'error')
            {
                if (empty($built['to'])) {
                    $built['to'] = $to;
                }

                if (empty($built['subject'])) {
                    $built['subject'] = $msg['subject'];
                }

                $res = send_email($built);
            }
            else {
                $res = $built;
            }
        }
        else
        {
            $res = send_email([
                'to'      => $to,
                'subject' => $msg['subject'],
                'body'    => $msg['message'],
            ]);
        }

        $attempts = $msg['attempts'] + 1;

        if (($res['code'] ?? '') === 'success')
        {
            query_it("
                UPDATE tb_queue_messages
                SET status='sent',
                    attempts={$attempts},
                    sent_at=NOW()
                WHERE id={$msg['id']}
            ");
        }
        else
        {
            $status = $attempts >= $max_attempts ? 'failed' : 'pending';

            query_it("
                UPDATE tb_queue_messages
                SET status='{$status}',
                    attempts={$attempts},
                    response='".addslashes(json_encode($res))."'
                WHERE id={$msg['id']}
            ");
        }
    }
}

/**
 * Deletes queue messages based on the configured retention period.
 *
 * The retention window is defined by `TIME_TO_DELETE_QUEUE_MESSAGES`.
 *
 * Notes:
 * - This function currently deletes records whose `created_at` is greater than
 *   or equal to `NOW() - INTERVAL X DAY`.
 * - In practice, retention cleanup usually removes records older than the interval,
 *   which would normally use `<=` instead of `>=`.
 *
 * @return bool Always returns true after executing the cleanup query.
 */
function clean_queue_messages()
{
    $interval = TIME_TO_DELETE_QUEUE_MESSAGES;
    query_it("DELETE FROM tb_queue_messages WHERE created_at >= NOW() - INTERVAL {$interval} DAY");
    return true;
}


/**
 * Build message content based on template path and params.
 *
 * @param string $template_name
 * @param array $params
 * @return array
 */
function build_email_message(string $template_name = 'this-system', array $payload = []): array
{
    global $seg;

    $file = resolve_template_path($template_name);

    if (!file_exists($file)) {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'template_not_found']
        ];
    }
    $params = $payload['template_params'] ?? [];

    ob_start();
    include $file;
    ob_end_clean();

    return $email_data ?? [];
}

/**
 * Generates the HTML content for the email using the default email_default_layout.
 *
 * @param array $args An associative array containing the email parameters.
 * Possible keys:
 *   - 'body': The main content of the email.
 *   - 'signature': An associative array for the signature (optional).
 *     Possible keys:
 *       - 'humanized': A boolean indicating if the signature is humanized.
 *       - 'image': The URL of the profile image (if humanized).
 *       - 'name': The name to display in the signature (if humanized).
 *       - 'task': The task or role to display in the signature (if humanized).
 *       - 'contact': The contact information to display in the signature (if humanized).
 * HTML content of the email.
 */
function email_default_layout(array $args = [])
{
    global $info;

    $res = "
    <!DOCTYPE html>
    <html>
    <style>
        .body {
            background-color: #f5f5f5;
            /*display: flex;*/
            font-family: 'montserrat', sans-serif;
        }
        .branding {
            background-color: {$info['brand_colors']['primary']};
            display: flex;
            padding: 2.5rem 0;
        }
        .branding img {
            margin: 0 auto;
        }
        .main {
            background-color: #fff;
            width: fit-content;
            max-width: 600px;
            margin: 0 auto;
        }
        .content {
            background-color: #fff;
            width: fit-content;
            max-width: 600px;
            margin: 0 auto;
            padding: 2.5rem 3rem;
        }
        .h1 {
            margin-top: 0;
            text-align: center;
            color: {$info['brand_colors']['primary']};
            font-size: 1.8em;
        }
        .h1 strong {
            color: {$info['brand_colors']['secondary']};
        }
        h3 {
            text-align: center;
            margin-bottom: 0;
        }
        p {
            font-size: 1rem;
            line-height: 1.5;
        }
        a {
            color: {$info['brand_colors']['secondary']} !important;
        }
        .align-itens-center {
            display: flex;
            padding: 1rem 0;
        }
        .btn {
            margin: 0 auto;
            color: #141414 !important;
            text-decoration: none;
            border-radius: 1.5rem;
            font-weight: 600;
            box-shadow: none !important;
            background-color: {$info['brand_colors']['primary']};
            border-color: {$info['brand_colors']['primary']};
            display: inline-block;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
        }
        .signature {
            display: flex;
            padding: 2.5rem;
            color: #fff !important;
            background-color: {$info['brand_colors']['secondary']};
        }
        .signature.system {
            display: flex;
        }
        .signature.system h3 {
            color: #fff !important;
            font-size: 1.25rem;
            text-align: left;
            margin: 0 auto 0 0;
        }
        .signature.humanized .person {
            display: flex;
        }
        .signature.humanized .person .profile {
            margin-right: 1.5rem;
            border-radius: 10rem;
            height: 70px;
            width: 70px;
        }
        .signature.humanized .person .about h3 {
            margin: 0 0 0.2rem 0;
            font-size: 1.2rem;
        }
        .signature.humanized .person .about .task {
            background-color: {$info['brand_colors']['primary']};
            color: #fff;
            margin-top: 0;
            padding: 0.1rem 0.4rem;
            width: fit-content;
            line-height: 1.5;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .signature .logo {
            height: fit-content;
            width: auto;
            margin-left: auto;
            max-height: 80px;
        }
    </style>
    <body class='body'>
    <section class='main'>
        <div class='branding'>
            <img width='220' src='". site_url('/uploads/images/brand/x-logotype-white.png?v=1') ."'>
        </div>
        <section class='content'>
            {$args['body']}
        </section>";

        $signature = $args['signature'] ?? false;
        $humanized = $signature['humanized'] ?? false;
        if ($humanized)
        {
            $res.= "
            <footer class='signature humanized'>
                <div class='person'>
                <img class='profile' src='{$signature['image']}' height='70' width='70'>

                <section class='about'>
                    <h3>{$signature['name']}</h3>
                    <p class='task'>{$signature['task']}</p>
                    <div class='contact'>{$signature['contact']}</div>
                </section>
                </div>

                <img class='logo' src='". site_url('/uploads/images/brand/isotype-white.png') ." '>
            </footer>";
        }

        else
        {
            $res.= "
            <footer class='signature system'>
                <h3>Abraços, <br> {$info['name']}</h3>
                <img class='logo' src='". site_url('/uploads/images/brand/isotype-white.png') ." '>
            </footer>";
        }

    $res.= "
    </section>
    </body>
    </html>";

    return $res;
}
