<?php

if (!function_exists('view_address_form_field'))
{
    function view_address_form_field(array $params = [])
    {
        extract($params);

        return format_address($field_value);
    }
}