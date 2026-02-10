<?php
if(!isset($seg)) exit;

global $email_providers;
$email_providers['brevo'] = 'Brevo';


define('API_KEY_BREVO', env('brevo.api.key'));
define('BREVO_EMAIL',   $info['email']);
define('BREVO_NAME',    $info['name']);


/**
 * Sends an email using the Brevo SMTP API.
 *
 * @param array $data An associative array containing the email data.
 *
 * @return array An associative array containing the result of the email sending operation.
 */
function brevo_send_email($data = [])
{
    $endpoint = 'https://api.brevo.com/v3/smtp/email';

    $args['sender'] = $data['sender'] ?? [
        'email' => BREVO_EMAIL,
        'name' => BREVO_NAME,
    ];

    $args['messageVersions'][] = [
        'to' => $data['to']
    ];

    $args['subject'] = $data['subject'];
    $args['htmlContent'] = $data['body'];


    $args = json_encode($args);
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS     => $args,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'api-key: ' . API_KEY_BREVO
        ]
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $msg = $response ?? curl_error($ch);
    curl_close($ch);

    return [
        'success' => ($http_code == 201) ? true : false,
        'msg'     => json_decode($msg, true)
    ];
}
