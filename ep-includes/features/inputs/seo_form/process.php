<?php

global $type_field;

/**
 *
 * Treatments for seo_form.
 *
 */
if (!function_exists('process_input_seo_form'))
{
    function process_input_seo_form(array $params = [])
    {
        extract($params);

        $res['value'] = $value;

        if (!empty($value['image']) && $value['image'] != '[]')
        {
            // Treat the format that was sent.
            if (is_json($value['image']))  $fileName = json_decode($value['image'], true)[0];
            elseif (is_array($value['image'])) $fileName = $value['image'][0];
            else $fileName = $value['image'];

            $final_name = build_final_filename(basename($fileName));

            $res['value']['image'] = $final_name;

            $res['pending_moves'] =
            [
                'temp_dir'   => TEMP_FILES_FOLDER,
                'dest_base'  => 'images/seo/',
                'files'      => [$fileName],
                'final_name' => $final_name,
                'is_update'  => ($mode === 'update'),
                'field'      => $key,
                'type'       => 'images',
            ];
        }


        return $res ?? [];
    }
}
