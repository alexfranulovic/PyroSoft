<?php
if(!isset($seg)) exit;

/**
 * Register a REST API route for manage a menu.
 *
 * This code registers a REST API route named 'manage-menu-system'.
 */
register_rest_route('manage-menu', [
  'methods' => ['POST', 'GET'],
  'need_login' => true,
  'callback' => function()
  {
    global $seg;

    feature('menu-management');       // Load the menu management functions.

    return manage_menu_system($_POST, $_GET['mode']);
  },
  'permission_callback' => '__return_true',
]);


register_rest_route('add-menu-item', [
  'methods' => ['POST'],
  'need_login' => true,
  'callback' => function()
  {
    global $seg;

    feature('menu-management');       // Load the menu management functions.

    $res = '';
    $counter = $_POST['counter'] ?? 1;

    foreach ($_POST['menu-item'] as $key => $value)
    {
      $id      = $value['id'] ?? '';

      $value['id']          = null;
      $value['depth']       = 0;
      $value['which_users'] = 'everyone';
      $value['style']       = 'generic';

      if ($key == 'page')
      {
        $page = get_page($id);

        $value['url']     = $id;
        $value['title']   = $page['title'];
      }

      elseif ($key == 'user_links')
      {
        $value['which_users'] = 'logged_in';
      }

      else
      {
        $value = $_POST['menu-item'][$key];
        $value['which_users'] = 'everyone';
      }

      $questions = menu_inputs_select_type($key, 'insert', $counter, $value);

      $res.= field_content_card(
        'update',
        [
          'delete' => true,
          'click' => true,
          'label' => $value['title'] ?? '',
          'depth' => 0,
          'counter' => "menu-item-{$counter}",
          'questions' => $questions,
        ]
      );

      $counter++;
    }

    return $res;
  },
  'permission_callback' => '__return_true',
]);
