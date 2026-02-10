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
 * Logger genérico em formato JSON por linha.
 *
 * @param string $level   error|warning|info|debug etc
 * @param string $message Mensagem principal
 * @param array  $context Dados extras (response, extra info, etc)
 */
function app_log(string $level, string $message, array $context = []): void
{
    // Captura dados do request
    $request = [
        'method'      => $_SERVER['REQUEST_METHOD'] ?? null,
        'uri'         => $_SERVER['REQUEST_URI'] ?? null,
        'query'       => $_GET ?? [],
        'post'        => $_POST ?? [],
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ];

    // Tenta pegar JSON bruto (quando for API)
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

    // Sessão / usuário logados (ajusta pro padrão do teu CMS)
    $session = [
        'session_id' => session_id() ?: null,
        'user_id'    => $_SESSION['current_user']['id'] ?? null, // troca pelo que você usa (id_usuario, admin_id etc)
    ];

    // Response (opcional): você passa no $context['response']
    $response = $context['response'] ?? null;
    if (isset($response['body']) && is_string($response['body'])) {
        // Não lotar o log com HTML gigante / JSON enorme
        $maxLen = 2000;
        if (strlen($response['body']) > $maxLen) {
            $response['body'] = substr($response['body'], 0, $maxLen) . '... [truncado]';
        }
    }

    // Se veio uma exception no contexto, serializa de forma bonitinha
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

    $record = [
        'timestamp' => date('c'),
        'origin'    => $context['origin'] ?? 'server',
        'level'     => $level,
        'message'   => $message,
        'request'   => $request,
        'session'   => $session,
        'response'  => $response,
        'exception' => $exceptionData,
        'context'   => $context, // resto do que você passar
    ];

    $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;

    if (!is_dir(APP_LOG_DIR)) {
        @mkdir(APP_LOG_DIR, 0775, true);
    }

    // Grava no arquivo
    @file_put_contents(APP_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

// Handler para warnings/notices convertidos em erro
set_error_handler(function ($severity, $message, $file, $line) {
    // Respeita o error_reporting atual
    if (!(error_reporting() & $severity)) {
        return false;
    }

    app_log('error', $message, [
        'php_error' => [
            'severity' => $severity,
            'file'     => $file,
            'line'     => $line,
        ],
    ]);

    // Se quiser derrubar a execução, joga exception
    // throw new ErrorException($message, 0, $severity, $file, $line);
    return false; // deixa o PHP seguir o fluxo padrão também
});

// Handler para exceptions não tratadas
// set_exception_handler(function (Throwable $e)
// {
//     app_log('error', $e->getMessage(), [
//         'exception' => $e,
//     ]);

//     // // Aqui você pode redirecionar pra uma página de erro amigável, etc
//     // http_response_code(500);
//     // if (php_sapi_name() === 'cli') {
//     //     fwrite(STDERR, "Erro fatal: {$e->getMessage()}" . PHP_EOL);
//     // } else {
//     //     echo "Ocorreu um erro interno. Tente novamente mais tarde.";
//     // }
// });

// Fatal errors (parse, memória, etc)
register_shutdown_function(function () {
  $error = error_get_last();
  if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
      app_log('fatal', $error['message'], [
          'php_error' => [
              'type' => $error['type'],
              'file' => $error['file'],
              'line' => $error['line'],
          ],
      ]);
  }
});
