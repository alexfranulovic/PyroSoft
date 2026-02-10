<?php

if (!function_exists('view_range_field'))
{
    function view_range_field(array $params = [])
    {
        extract($params);

        $value = $field_value;

        $prefix = $field['prefix'] ?? '';

        // No data → return nothing
        if ($value === null || $value === '') {
            return null;
        }

        // Case 1: Simple range (single value)
        if (!is_array($value) && strpos($value, ',') === false) {
            return htmlspecialchars("$prefix $value", ENT_QUOTES);
        }

        // Case 2: String range: "min,max"
        if (!is_array($value)) {
            $parts = explode(',', $value);
            $min   = trim($parts[0] ?? '');
            $max   = trim($parts[1] ?? '');

            return htmlspecialchars("{$prefix} {$min} – {$prefix} {$max}", ENT_QUOTES);
        }

        // Case 3: Array format: ['min' => X, 'max' => Y]
        $min = $value['min'] ?? null;
        $max = $value['max'] ?? null;

        // Single-value range (only min)
        if ($min !== null && $max === null) {
            return htmlspecialchars("{$prefix} $min", ENT_QUOTES);
        }

        // Full interval range
        if ($min !== null && $max !== null) {
            return htmlspecialchars("{$prefix} {$min} – {$prefix} {$max}", ENT_QUOTES);
        }

        return null;
    }
}
