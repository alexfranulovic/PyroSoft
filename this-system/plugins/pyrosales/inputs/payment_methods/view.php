<?php

if (!function_exists('view_payment_methods_field'))
{
    function view_payment_methods_field(array $params = [])
    {
        extract($params);

        return $field_value;
    }
}
