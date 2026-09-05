<?php
if(!isset($seg)) exit;

/**
 * Register a REST API route for manage a PWA.
 *
 * This code registers a REST API route named 'generate-pwa-manifest'.
 */
register_rest_route('generate-pwa-manifest', [
    'methods' => 'POST',
    'callback' => function()
    {
        pwa_load_functions();

        $permission = load_permission('pwa-manager', 'custom');
        if (!$permission) {
          return invalid_permission_response();
        }

        if (empty($_POST)) return 'No information was given to create the manifest.json.';

        $mode = $_POST['mode'];
        unset($_POST['process-form'], $_POST['mode']);
        $_POST['generated_by'] = 'EUPHORIA SYSTEMS';

        return generate_pwa_manifest($_POST, $mode);
    },
    'need_login' => true,
    'permission_callback' => '__return_true',
]);
