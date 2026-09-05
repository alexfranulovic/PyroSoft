<?php
if (!isset($seg)) exit;

add_asset('head', "<link rel='stylesheet' href='". plugin_path('/plans-subscriptions/assets/styles/users-subscriptions-timeline.css', 'url') ."'>");
add_asset('footer', "<script src='" . plugin_path('/plans-subscriptions/assets/scripts/subscriptions.js', 'url') . "' defer></script>");

include_once AREAS_PATH .'/admin/include/head.php';
include_once AREAS_PATH .'/admin/include/menu.php';

$user_id = (int) ($_GET['user_id'] ?? 0);
$user    = $user_id > 0 ? get_result("SELECT id, first_name, last_name, email FROM tb_users WHERE id = '{$user_id}' LIMIT 1") : null;


$header_params = empty($user['id'])
  ? []
  : [
    'title' => 'Jornada de assinaturas',
    'subtitle' => e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ." &middot; ". e((string)($user['email'] ?? '')),
  ];

if (!empty($_GET['user_id']))
{
  $header_params['hooks_out'] = [
    [
      'color' => 'outline-primary',
      'link' => get_url_page('subscriptions', 'full'),
      'title' => 'Voltar',
    ],
  ];
}

pageBaseTop($header_params);
feature('listings');
?>

<main class="row m-0" role="main">
<section class="col-12 module">
<?php

if (!empty($_GET['user_id']))
{
  echo !empty($user['id'])
    ? render_user_plans_timeline_html($user_id)
    : "<div class='alert alert-warning'>Usuário não encontrado.</div>";
}

else
{
  echo custom_listing_table('plugin/plans-subscriptions', 'subscriptions', [
    'title' => 'Subscriptions',
  ]);
}

?>
</section>
</main>

<?php include_once AREAS_PATH .'/admin/include/script_libs.php'; ?>
