<?php

if (!function_exists('view_basic_field'))
{
    function view_basic_field(array $params = [])
    {
        extract($params);

        // View and Access URL
        if ($field['type'] == 'url') {
            $value = view_media($field_value, 'url');
        }

        // Price format
        elseif ($field['type'] == 'price') {
            $value = BRL( $field_value );
        }

        return $value ?? null;
    }
}