<?php
if(!isset($seg)) exit;

function log_it(string $archive, string $content = '')
{
    $folder = APP_LOG_DIR;
    $file   = $folder . '/' . $archive;

    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    file_put_contents($file, $content, FILE_APPEND);

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (count($lines) > 1000) {
        $lines = array_slice($lines, -1000);
        file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}

/**
 * Generic JSON line logger.
 *
 * Features:
 * - Creates daily folders: logs/YYYY-MM-DD/
 * - Allows custom log file name through $context['file_name']
 * - Stores one JSON record per line
 *
 * @param string $level   error|warning|info|debug|fatal etc
 * @param string $message Main log message
 * @param array  $context Extra context data
 *
 * @return void
 */
function app_log(string $level, string $message, array $context = []): void
{
    /**
     * Resolve final log file name.
     */
    $fileName = (string)($context['file_name'] ?? 'app.log');
    unset($context['file_name']);

    $fileName = trim($fileName);

    if ($fileName === '') {
        $fileName = 'app.log';
    }

    /**
     * Sanitize file name to avoid invalid paths or traversal.
     */
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '-', $fileName);
    $fileName = ltrim($fileName, '.-');

    if ($fileName === '') {
        $fileName = 'app.log';
    }

    if (stripos($fileName, '.log') === false) {
        $fileName .= '.log';
    }

    /**
     * Build dated directory path: logs/YYYY-MM-DD/
     */
    $dateFolder = date('Y-m-d');
    $logDir = rtrim(APP_LOG_DIR, '/\\') . DIRECTORY_SEPARATOR . $dateFolder;
    $logFile = $logDir . DIRECTORY_SEPARATOR . $fileName;

    /**
     * Capture request data.
     */
    $request = [
        'method'      => $_SERVER['REQUEST_METHOD'] ?? null,
        'uri'         => $_SERVER['REQUEST_URI'] ?? null,
        'query'       => $_GET ?? [],
        'post'        => $_POST ?? [],
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ];

    /**
     * Try to capture raw JSON body for API requests.
     */
    $rawInput = null;
    if (
        isset($_SERVER['CONTENT_TYPE'])
        && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
    ) {
        $rawInput = file_get_contents('php://input');
    }

    if ($rawInput) {
        $request['raw_body'] = $rawInput;
    }

    /**
     * Logged session / current user.
     */
    $session = [
        'session_id' => session_id() ?: null,
        'user_id'    => $_SESSION['current_user']['id'] ?? null,
    ];

    /**
     * Optional response passed in context.
     */
    $response = $context['response'] ?? null;
    if (isset($response['body']) && is_string($response['body'])) {
        $maxLen = 2000;
        if (strlen($response['body']) > $maxLen) {
            $response['body'] = substr($response['body'], 0, $maxLen) . '... [truncated]';
        }
    }

    /**
     * Serialize exception if present.
     */
    $exceptionData = null;
    if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
        /** @var Throwable $e */
        $e = $context['exception'];

        $exceptionData = [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => explode("\n", $e->getTraceAsString()),
        ];

        unset($context['exception']);
    }

    /**
     * Final log record.
     */
    $record = [
        'timestamp' => date('c'),
        'origin'    => $context['origin'] ?? 'server',
        'level'     => $level,
        'message'   => $message,
        'context'   => $context,
        'request'   => $request,
        'response'  => $response,
        'session'   => $session,
        'exception' => $exceptionData,
    ];

    $line = json_encode(
        $record,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    ) . PHP_EOL;

    /**
     * Ensure dated log directory exists.
     */
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    /**
     * Write log line.
     */
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Error handler for warnings/notices converted into logs.
 */
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    app_log('error', $message, [
        'file_name' => 'php-errors.log',
        'php_error' => [
            'severity' => $severity,
            'file'     => $file,
            'line'     => $line,
        ],
    ]);

    return false;
});

/**
 * Fatal errors handler.
 */
register_shutdown_function(function () {
    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        app_log('fatal', $error['message'], [
            'file_name' => 'php-fatal.log',
            'php_error' => [
                'type' => $error['type'],
                'file' => $error['file'],
                'line' => $error['line'],
            ],
        ]);
    }
});
