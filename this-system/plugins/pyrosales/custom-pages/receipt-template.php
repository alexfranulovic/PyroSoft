<?php
if (!isset($seg)) exit;



/**
 *
 * Heads and Libs.
 *
 */
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/checkout.css', 'url') ."'>");
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/card-hero.css', 'url') ."'>");


/**
 *
 * Load order
 *
 */
$order_id     = $params_url[1] ?? null;
$order        = get_order($order_id, true, true);
$items        = $order['items'] ?? [];
$plan_id      = $items[0]['plan_id'] ?? null;
$coupon_lines = $order['coupon_lines'] ?? [];
$fee_lines    = $order['fee_lines'] ?? [];
$payments     = $order['payments'] ?? [];
$currency     = $order['currency'] ?? DEFAULT_CURRENCY;
$order        = $order['order'] ?? [];
$sub          = get_subscription($order['subscription_id'] ?? 0);
$plan         = $plan = get_result("
  SELECT
    plan.*
  FROM tb_plans AS plan
  LEFT JOIN tb_order_items AS item ON item.plan_id = plan.id
  LEFT JOIN tb_orders AS ord ON ord.id = item.order_id
  WHERE
    ord.id = '{$order_id}'
  LIMIT 1
");


/**
 *
 * Sale price
 *
 */
$tag = 'bdi';
$discount = '';
if (!empty($order['sale_price'])) {
  $tag = 's';
  $discount = (($order['regular_price'] - $order['sale_price']) / $order['regular_price']) * 100;
  $discount = number_format($discount, 0);
}


/**
 *
 * UI
 *
 */
include_once AREAS_PATH .'/app/include/head.php';
include_once AREAS_PATH .'/app/include/menu.php';
?>

<main class="pt-0 container" role="main">
<?php if (!empty($order)): ?>

  <section>
  <div class="card hero">
  <div class="card-body">
    <div class="big-icon success"><?= icon('fas fa-check') ?></div>

    <?php if($order['order_type'] == 'plan'): ?>
    <h1>Bem vindo(a) a comunidade!</h1>
    <p>Seu pagamento foi processado com sucesso e seu plano já está ativo!</p>

    <?php elseif($order['order_type'] == 'one_off'): ?>
    <h1>Compra realizada com sucesso!</h1>
    <p>Seu pagamento foi processado e seu item já está liberado para uso!</p>

    <?php endif;?>

  </div>
  </div>

  <div class="card">
  <div class="card-body">

    <table>
    <tbody>

      <?php if($order['order_type'] == 'plan'): ?>

      <tr>
        <td>Plano adquirido</td>
        <td class="data"><strong><?= $items[0]['item_name'] ?></strong></td>
      </tr>
      <tr>
        <td>Data de renovação</td>
        <td class="data"><strong><?= date('d/m/Y', strtotime($sub['next_billing_at'])) ?></strong></td>
      </tr>

      <?php elseif($order['order_type'] == 'one_off'): ?>

      <tr>
        <td>Item adquirido</td>
        <td class="data"><strong><?= $items[0]['item_name'] ?></strong></td>
      </tr>

      <tr>
        <td>Quantidade</td>
        <td class="data"><strong><?= $items[0]['quantity'] ?></strong>x</td>
      </tr>

      <?php endif; ?>

      <tr>
        <td>Forma de pagamento</td>
        <td class="data"><strong><?= $payments[0]['method'] ?? 0 ?></strong></td>
      </tr>
      <tr>
        <td>Valor pago</td>
        <td class="data"><strong><?= $currency($payments[0]['amount'] ?? 0) ?></strong></td>
      </tr>
      <tr>
        <td>ID do pedido</td>
        <td class="data"><strong>#<?= $order['id'] ?></strong></td>
      </tr>

    </tbody>
    </table>

  <hr>

  <a href="<?= site_url('/perfil') ?>" class="btn btn-st btn-block">Ir para meu Dashboard</a>
  <!-- <a href="<?= site_url('/perfil') ?>" class="btn btn-link btn-block">Acessar minha conta</a> -->
  </div>
  </div>

  <div class="card">
  <div class="card-body details">
    <?php
    if($order['order_type'] == 'plan'){
      $description = $plan['description'] ?? '';
    }
    elseif($order['order_type'] == 'one_off'){
      $item_slug = $items[0]['slug'] ?? '';
      $description = $GLOBALS['one_off_catalog'][$item_slug]['description'] ?? '';
    }


    if (!empty($description))
    {
      $description = format_text(nl2br($description));
      echo "
      <h2>Benefícios liberados</h2>
      <p>{$description}</p>";
    }
    ?>
    <div class="disclaimer">
    <?php
    echo block('alert',
    [
      'body' => icon("fas fa-info-circle") .' Complete seu perfil com os Fires para aparecer mais nas buscas.',
      'variation' => 'alert-disclaimer',
      'close_button' => false,
      'color' => 'primary'
    ]);
    ?>
    </div>
  </div>
  </div>

  </section>

<?php else: ?>

  <div class="card hero">
  <div class="card-body">
    <div class="big-icon error"><?= icon('fas fa-money-bill-wave') ?></div>
    <h1>Não foi possível localizar esse pedido ou ele não pertence a você.</h1>
  </div>
  </div>

<?php endif; ?>
</main>

<?php include_once AREAS_PATH .'/app/include/footer.php'; ?>
