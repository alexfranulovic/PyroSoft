<?php

if (!function_exists('view_status_selector_field'))
{
    function view_status_selector_field(array $params = [])
    {
        extract($params);

        $value = !$permissions['update']
            ? general_stats($field_value, $function_proccess)
            : status_buttons($id, $field_value, $table_crud, $function_proccess);

        return $value ?? null;
    }
}