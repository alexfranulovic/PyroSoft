<?php

/**
 *
 * Treatments for basic.
 *
 */
if (!function_exists('process_input_basic'))
{
    function process_input_basic(array $params = [])
    {
        extract($params);

        if ($type == 'password' AND !empty($value)) {
            $res['value'] = password_encrypt($value);
        }

        elseif ($type == 'password' AND empty($value)) {
            $res['must_continue'] = true;
        }

        return $res ?? [];
    }
}