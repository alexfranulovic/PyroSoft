<?php

$related_to = $crud['related_to'] ?? 'table';

/**
 *
 * Treatments for upload inputs.
 *
 */
if (!function_exists('process_input_upload'))
{
    function process_input_upload(array $params = [])
    {
        extract($params);

        $related_to    = $crud['related_to'] ?? 'table';
        $upload_to_s3  = !empty($field['upload_to_s3']);
        $type          = $field['type'] ?? 'archives';
        $final_name    = (string)($field['final_name'] ?? '');
        $Src           = $field['Src'] ?? DEFAULT_IMAGES_FOLDER;
        $folder        = "{$type}/{$Src}/";

        if ($related_to != 'system_info' && $mode === 'update') {
            $folder .= "{$parent_register_id}/";
        }

        // 1) Normalize value from front (string | json | array)
        $raw = $data[$key] ?? null;

        if (is_array($raw) && isset($raw[0]) && is_string($raw[0]) && is_json($raw[0])) {
            $files = json_decode($raw[0], true);
        } elseif (is_string($raw) && is_json($raw)) {
            $files = json_decode($raw, true);
        } elseif (is_string($raw) && strlen($raw)) {
            $files = [$raw];
        } elseif (is_array($raw)) {
            $files = $raw;
        } else {
            $files = [];
        }

        /**
         * IMPORTANT:
         * - We must MOVE using the TEMP name (because that's what exists in TEMP/S3 temp).
         * - We must SAVE to DB using the FINAL name (final_name forced, suffix removed).
         */
        $tempNames  = [];
        $finalNames = [];

        foreach ($files as $item)
        {
            // supports object payloads too (url/name)
            if (is_array($item)) {
                $item = $item['url'] ?? ($item['name'] ?? '');
            }

            $item = trim((string)$item);
            if ($item === '' || strpos($item, 'data:') === 0) continue;

            // extract only basename (in case it's a URL or path)
            $path = parse_url($item, PHP_URL_PATH) ?: $item;

            $temp = basename($path);
            if ($temp === '') continue;

            $tempNames[]  = $temp;
            $finalNames[] = is_temp_filename($temp)
                ? build_final_filename($temp, $final_name)
                : $temp;
        }

        $tempNames  = array_values(array_filter($tempNames));
        $finalNames = array_values(array_filter($finalNames));

        // 2) Value to persist in DB: FINAL names
        $isMultiple  = !empty($field['multiple']);
        $res['value'] = $isMultiple ? $finalNames : ($finalNames[0] ?? null);

        if (empty($res['value'])) {
            return [];
        }

        // 3) Schedule movements temp -> final (executes in post-query)
        $res['pending_moves'] =
        [
            'storage'    => $upload_to_s3 ? 's3' : 'local',
            'temp_dir'   => TEMP_FILES_FOLDER,
            'dest_base'  => $folder,
            'files'      => $tempNames,
            'final_name' => $final_name,
            'is_update'  => ($mode === 'update'),
            'field'      => $key,
            'Src'        => $Src,
            'type'       => $type,
        ];

        return $res ?? [];
    }
}
