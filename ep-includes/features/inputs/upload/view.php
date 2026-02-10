<?php

if (!function_exists('view_upload_field'))
{
    function view_upload_field(array $params = [])
    {
        extract($params);

        $folder = $field['type'];

        $items = is_string($field_value) ? [$field_value] : $field_value;

        $values = [];
        if (is_array($items) || is_object($items))
        {
            foreach ($items as $item) {
                $values[] = file_url("{$folder}/{$field['Src']}/{$id}", $field['upload_to_s3'], $item);
            }
        }

        $field_value = $values;

        $value = view_media( $field_value, $field['type'], 'view' );

        return $value ?? null;
    }
}
