<?php
if (!isset($seg)) exit;

global $tables;
$tables['tb_feature_metrics'] = 'Métricas de featues';

function feature_metric(string $feature_name, $type = 2, bool $log_view = true)
{
    global $current_user;

    $feature_name = trim($feature_name);
    if ($feature_name === '') {
        return '';
    }

    // Store a view when rendering, if desired
    if ($log_view) {
        $user_id = null;

        $user_id = is_user_logged_in()
            ? $current_user['id']
            : null;

        $insert_data = [
            'feature_name' => $feature_name,
            'type'         => 1,
        ];
        if (!empty($user_id)) $insert_data['user_id'] = $user_id;

        try {
            insert('tb_feature_metrics', $insert_data);
        }

        catch (Throwable $e) { }
    }

    $featAttr = htmlspecialchars($feature_name, ENT_QUOTES, 'UTF-8');
    $typeAttr = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');

    // Caller can add feature-metric-open-modal if this is a future feature
    return "feature-metric-name=\"{$featAttr}\" feature-metric-type=\"{$typeAttr}\" feature-metric-open-modal";
}

add_asset('footer', "<script src='". plugin_path('/feature-metrics/assets/scripts/main.js', 'url') ."' defer></script>");
