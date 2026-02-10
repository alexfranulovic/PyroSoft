<?php
if (!isset($seg)) exit;

/*
 * Register REST route for managing plugin lifecycle
 */
register_rest_route('manage-plugins', [
  'methods'  => 'GET',
  'callback' => function ()
  {
    global $config;

    $permission = load_permission('manage-plugins', 'custom');

    if (!$permission) {
      return invalid_permission_response();
    }

    // Params
    $plugin = isset($_GET['plugin']) ? trim((string) $_GET['plugin']) : '';
    $action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';

    if ($plugin === '' || $action === '')
    {
      return [
        'code'    => 'error',
        'message' => 'Parameters "plugin" and "action" are required.',
      ];
    }

    // Ensure activated_plugins structure exists
    if (!isset($config['activated_plugins']) || !is_array($config['activated_plugins'])) {
        $config['activated_plugins'] = [];
    }

    $plugins_folder = PLUGINS_ABSOLUTE_PATH;
    $plugin_dir     = rtrim($plugins_folder, '/\\') . DIRECTORY_SEPARATOR . $plugin;

    if (!is_dir($plugin_dir))
    {
      return [
        'code'    => 'error',
        'message' => "Plugin '{$plugin}' not found in {$plugins_folder}.",
      ];
    }

    $activated = $config['activated_plugins'];
    $changed   = false;

    switch ($action)
    {
      case 'enable':
        // Add plugin if it is not already active
        if (!in_array($plugin, $activated, true))
        {
          $activated[] = $plugin;
          $activated   = array_values(array_unique($activated));

          $config['activated_plugins'] = $activated;

          update_option('activated_plugins', $activated, true);
          $changed = true;
        }

        // Execute plugin's install.php
        load_plugins('install', $plugin, true);
        break;


      case 'disable':
        // Apenas remove dos ativos (sem rodar uninstall.php)
        $idx = array_search($plugin, $activated, true);
        if ($idx !== false)
        {

          unset($activated[$idx]);
          $activated = array_values($activated);
          $config['activated_plugins'] = $activated;

          update_option('activated_plugins', $activated, true);
          $changed = true;
        }
        break;


      case 'uninstall':
        // Run uninstall.php while still active
        load_plugins('uninstall', $plugin, true);


        // Remove from active plugins
        $idx = array_search($plugin, $activated, true);
        if ($idx !== false)
        {
          unset($activated[$idx]);
          $activated = array_values($activated);
          $config['activated_plugins'] = $activated;

          update_option('activated_plugins', $activated, true);
          $changed = true;
        }
        break;


      default:
        $changed = false;
        break;
    }

    return [
      'code'              => 'success',
      'message'           => 'Action executed successfully.',
      'plugin'            => $plugin,
      'action'            => $action,
      'activated_plugins' => $activated,
      'config_updated'    => $changed,
    ];
  },
  'permission_callback' => '__return_true',
]);
