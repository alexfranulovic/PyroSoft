<?php

if (!function_exists('process_input_g_recaptcha'))
{
    /**
     * Field processor: g_recaptcha
     *
     * - Validates Google reCAPTCHA token using env() keys.
     * - Never persists anything (must_continue = true).
     * - Stops the form processing on failure (must_break = true).
     * - G_RECAPTCHA_SECRET_KEY (required)
     */
    function process_input_g_recaptcha(array $ctx = [])
    {
        // This field is validation-only: never persist it
        $must_continue = true;
        $must_break    = false;

        $detail = $ctx['detail'] ?? '';

        $secret = (string) env('G_RECAPTCHA_SECRET_KEY');
        if ($secret === '') {
            return [
                'must_continue' => true,
                'must_break'    => true,
                'detail'        => 'reCAPTCHA is not configured (missing secret key).',
                'value'         => $ctx['value'] ?? '',
            ];
        }

        // Token source priority:
        // 1) Field value inside pipeline
        // 2) Google default POST key
        // 3) Fallback custom POST key
        $resp = (string) ($ctx['value'] ?? '');
        if ($resp === '') $resp = (string) ($_POST['g-recaptcha-response'] ?? '');
        if ($resp === '') $resp = (string) ($_POST['g_recaptcha'] ?? '');

        if ($resp === '') {
            return [
                'must_continue' => true,
                'must_break'    => true,
                'detail'        => 'Please confirm you are not a robot (reCAPTCHA missing).',
                'value'         => $ctx['value'] ?? '',
            ];
        }

        $remoteip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $timeout  = 6;

        $postFields = [
            'secret'   => $secret,
            'response' => $resp,
        ];
        if ($remoteip !== '') {
            $postFields['remoteip'] = $remoteip;
        }

        // Prefer cURL for better reliability; fallback to file_get_contents
        $raw = '';
        $httpOk = false;

        if (function_exists('curl_init')) {
            $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($postFields),
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            ]);

            $raw = (string) curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrNo = curl_errno($ch);
            curl_close($ch);

            if (!$curlErrNo && $httpCode >= 200 && $httpCode < 300 && $raw !== '') {
                $httpOk = true;
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($postFields),
                    'timeout' => $timeout,
                ],
            ]);

            $raw = (string) @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
            if ($raw !== '') {
                $httpOk = true;
            }
        }

        if (!$httpOk) {
            return [
                'must_continue' => true,
                'must_break'    => true,
                'detail'        => 'Unable to validate reCAPTCHA right now. Please try again.',
                'value'         => $ctx['value'] ?? '',
            ];
        }

        $j = json_decode($raw, true);
        $ok = is_array($j) && !empty($j['success']);

        if (!$ok) {
            // Friendly message; do not leak Google internals to user
            return [
                'must_continue' => true,
                'must_break'    => true,
                'detail'        => 'reCAPTCHA validation failed. Please try again.',
                'value'         => $ctx['value'] ?? '',
            ];
        }

        // Passed => continue pipeline, but do not persist
        return [
            'must_continue' => $must_continue,
            'must_break'    => $must_break,
            'detail'        => $detail,
            'value'         => $ctx['value'] ?? '',
        ];
    }
}
