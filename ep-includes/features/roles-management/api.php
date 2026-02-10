<?php
if(!isset($seg)) exit;

/**
 * Register a REST API route for manage a role.
 *
 * This code registers a REST API route named 'manage-user-role-system'.
 */
register_rest_route('manage-user-role', [
  'methods' => ['POST', 'GET'],
  'callback' => function()
  {
    global $seg;

    feature('roles-management');       // Load the role management functions.

    return manage_user_role_system($_POST, $_GET['mode']);
  },
  'permission_callback' => '__return_true',
]);
