<?php
if (!isset($seg)) exit;

register_rest_route('feature-metric', [
    'methods'  => 'POST',
    'callback' => function ()
    {
        global $current_user;

        $feature_name = trim($_POST['feature_name'] ?? '');
        $type         = trim($_POST['type'] ?? '');
        $allowed      = ['1', '2', '3'];

        if ($feature_name === '' || !in_array($type, $allowed, true))
        {
            return [
                'code'    => 'error',
                'message' => 'Invalid feature_name or type.',
            ];
        }

        // Try to resolve current user
        $user_id = is_user_logged_in()
            ? $current_user['id']
            : null;

        $insert_data = [
            'feature_name' => $feature_name,
            'type'         => $type,
        ];

        if (!empty($user_id)) {
            $insert_data['user_id'] = $user_id;
        }

        try {
            insert('tb_feature_metrics', $insert_data);
        } catch (Throwable $e) {
            // Optional: log this somewhere
            return [
                'code'    => 'error',
                'message' => 'Failed to insert feature event.',
            ];
        }

        return [
            'code'          => 'success',
            'message'       => 'Event registered.',
            'feature_name'  => $feature_name,
            'type'          => $type,
            'user_id'       => $user_id,
        ];
    },
    'permission_callback' => '__return_true',
]);
