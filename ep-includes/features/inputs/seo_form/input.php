<?php

function input_seo_form(string $type_form, array $Attr = [])
{
    extract($Attr);

    $value = $Value ?? null;

    return SEO_form($type_form, [
        'name' => $name ?? null,
        'access_count' => $value['access_count'] ?? 0,
        'value' => $value,
        'mode' => $mode ?? null,
    ]);
}
