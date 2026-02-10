<?php

/**
 *
 * Treatments for password.
 *
 */
if (!function_exists('process_input_password'))
{
    function process_input_password(array $params = [])
    {
        global $login_settings;

        extract($params);

        $type = $field['type'] ?? 'default';

        if ($type == 'default')
        {
            if (!empty($value)) {
                $res['value'] = password_encrypt($value);
            }

            elseif (empty($value)) {
                $res['must_continue'] = true;
            }
        }

        elseif ($type == 'new-password')
        {
            $password = isset($value['value']) ? (string) $value['value'] : '';
            $repeat   = isset($value['repeat']) ? (string) $value['repeat'] : '';

            if ($password == $repeat) {
                $res['value'] = password_encrypt($password);
            }

            else
            {
                $message_code = 'ER_INVALID_REPETITION_PASSWORD';
                $res['detail'] = [
                    'type' => 'toast',
                    'msg' => alert_message($message_code, 'toast'),
                    'code' => $message_code,
                ];

                $res['must_break'] = true;
            }
        }

        return $res ?? [];
    }
}
