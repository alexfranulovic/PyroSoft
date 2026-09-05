<?php
if (!isset($seg)) exit;

$order_id     = null;
$currency     = DEFAULT_CURRENCY;
$one_off_item = null;
$plan_id      = null;
$plan         = [];
$is_checkout  = !(!empty($params_url[1]) && $params_url[1] == "pay");
$is_one_off   = (!empty($params_url[1]) && $params_url[1] == "one-off");

/**
 *
 * Heads and Libs.
 *
 */
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/card-hero.css', 'url') ."'>");
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/checkout.css', 'url') ."'>");
add_asset('footer', "<script src='". plugin_path('/pyrosales/assets/scripts/checkout.js', 'url') ."' defer></script>");


/**
 *
 * Load plan
 *
 */
if ($is_checkout)
{
  checkout_load_gateways_head();

  /**
   *
   * One-off checkout
   *
   * URL: /checkout/one-off?item_slug=<catalog_key>&quantity=<n> -- same two
   * params manage_one_off_cart() takes, so they're just forwarded straight
   * through. item_slug is looked up in $GLOBALS['one_off_catalog'][<key>],
   * populated by your own code (see src/one-off-cart.php). Once it's in the
   * cookie, reloading this same page without item_slug keeps using it.
   *
   */
  if ($is_one_off)
  {
    if (!empty($_GET['item_slug']))
    {
      manage_one_off_cart('set', [
        'item_slug' => $_GET['item_slug'],
        'quantity'  => (int) ($_GET['quantity'] ?? 1),
      ]);
    }

    $one_off_item = get_one_off_cart_item();

    if (!empty($one_off_item))
    {
      $item_title     = $one_off_item['item_name'];
      $item_qty       = (int) $one_off_item['quantity'];
      $amount         = round(((float) $one_off_item['unit_price']) * $item_qty, 2);
      $regular_amount = round(((float) $one_off_item['regular_unit_price']) * $item_qty, 2);

      $tag = 'bdi';
      $discount = $discount_percent = '';
      if ($regular_amount > 0 && $amount < $regular_amount)
      {
        $tag = 's';
        $discount         = $regular_amount - $amount;
        $discount_percent = ($discount / $regular_amount) * 100;
        $discount_percent = number_format($discount_percent, 0);
      }
    }
  }

  /**
   *
   * Plan checkout
   *
   * Needs plan_id param
   *
   */
  elseif(!empty($_GET['plan_id']))
  {
    $plan_id  = $_GET['plan_id'];
    $plan     = get_plan($plan_id);

    if (!empty($plan))
    {
      /**
       *
       * Sale price
       *
       */
      $currency = $plan['currency'] ?? DEFAULT_CURRENCY;
      $item_title     = $plan['name'];
      $regular_amount = $plan['regular_price'];
      $amount = ($plan['sale_price'] > 0)
        ? $plan['sale_price']
        : $plan['regular_price'];

      $tag = 'bdi';
      $discount = $discount_percent = '';
      if ($plan['sale_price'] > 0)
      {
        $tag = 's';
        $discount         = $regular_amount - $amount;
        $discount_percent = (($plan['regular_price'] - $plan['sale_price']) / $plan['regular_price']) * 100;
        $discount_percent = number_format($discount, 0);
      }


      $unit      = $plan['interval_unit'] ?? null;
      $count     = $plan['interval_count'] ?? null;
      $intervals =
      [
        'singular' => [
          'day' => 'dia',
          'week' => 'semana',
          'month' => 'mês',
          'year' => 'ano',
        ],
        'plural' => [
          'day' => 'dias',
          'week' => 'semanas',
          'month' => 'mêses',
          'year' => 'anos',
        ],
      ];
      // dump($plan);

      $variation = ($count == 1)
        ? 'singular'
        : 'plural';

      $unit_display = (($count == 1) && !empty($intervals[$variation][$unit]))
        ? "<span class='interval-display'>/{$intervals['singular'][$unit]}</span>"
        : "<span class='interval-display-full'> a cada {$count} {$intervals[$variation][$unit]}</span>";

    }
  }

  $item_exists = !empty($plan) || !empty($one_off_item);
}

/**
 *
 * Load order
 *
 */
elseif (!$is_checkout && !empty($params_url[2]))
{
  $order_id = $params_url[2] ?? null;
  $order = get_order($order_id, true, true);

  $items        = $order['items'] ?? [];
  $plan_id      = $items[0]['plan_id'] ?? null;
  $coupon_lines = $order['coupon_lines'] ?? [];
  $fee_lines    = $order['fee_lines'] ?? [];
  $payments     = $order['payments'] ?? [];
  $order        = $order['order'] ?? [];
  $currency     = $order['currency'] ?? DEFAULT_CURRENCY;

  // Whether this order is a one-off purchase (no plan_id/no fixed DB row) --
  // used below to adapt the pix/expired copy and the "buy again" link.
  $order_is_one_off = ($items[0]['item_type'] ?? null) === 'one_off';
  $order_item_name  = $items[0]['item_name'] ?? null;
}

/**
 *
 * Checkout fields
 *
 */
/**
 *
 * If user is not logged in show the user form
 *
 */
if (!is_user_logged_in())
{
  /**
   *
   * Customer name treatment
   *
   */
  if (!empty($current_user['first_name'])) {
    $name[] = $current_user['first_name'];
  }

  if (!empty($current_user['last_name'])) {
    $name[] = $current_user['last_name'];
  }

  $customer = [
    "title" => "Dados pessoais",
    // "icon" => "fas fa-id-badge",
    "type_field" => "divider",
    'description' => "<p>Os seus dados de pagamento são criptografados e processados de forma segura.</p>",
    "depth" => 0,
    "childs" => [
      [
        "depth" => 1,
        'size' => 'col-12',
        "label" => "Nome completo",
        "Required" => 1,
        "type_field" => "basic",
        "Value" => implode(' ', $name ?? []) ?? '',
        "name" => "customer[name]",
      ],
      [
        "depth" => 1,
        "label" => "E-mail",
        "type" => "email",
        "function_process" => "auto_fill_name_by_cpf([email])",
        "attachment" => [
          "append" => "@email.com"
        ],
        "Required" => 1,
        "type_field" => "basic",
        "Value" => $current_user['email'] ?? '',
        "name" => "customer[email]",
      ],
      // [
      //   "depth" => 1,
      //   "label" => "Senha",
      //   // "type" => "new-password",
      //   "type_field" => "password",
      //   // "Value" => $current_user['password'],
      //   "name" => "customer[password]",
      //   "Required" => 1,
      // ],
      [
        "depth" => 1,
        "label" => "Telefone",
        'class' => 'mask-phone',
        "type_field" => "basic",
        "Value" => $current_user['phone'] ?? '',
        "name" => "customer[phone]",
        "Required" => 1,
      ],
      [
        "depth" => 1,
        "label" => "CPF",
        'class' => 'mask-cpf',
        "type_field" => "basic",
        "Value" => $current_user['document_number'] ?? '',
        "name" => "customer[document_number]",
        "Required" => 1,
      ],
      [
        "depth" => 1,
        "type_field" => "hidden",
        // "Value" => $current_user['document_type'],
        "name" => "customer[document_type]",
        'Value' => 'CPF',
      ],
    ]
  ];

  $checkout_fields[] = $customer;
}


$payment_data = [
  "title" => "Escolha como pagar",
  // "icon" => "fas fa-money-bill",
  "type_field" => "divider",
  "depth" => 0,
  "childs" => [
    [
      "depth" => 1,
      "type_field" => "payment_methods",
      'plan_id' => $plan_id,
      'one_off' => $is_one_off,
      'only_these' => $plan['accepted_payment_methods'] ?? [],
      'custom_statement_descriptor' => true,
    ],
    [
      "depth" => 1,
      "type_field" => "break_line",
    ],
    [
      "depth" => 1,
      "type" => "checkbox",
      "Options" => [[
        "value" => 1,
        "display" => $is_one_off
          ? "Ao concluir a compra eu declaro estar de acordo e ciente dos termos de uso."
          : "Ao me tornar membro(a) eu declaro estar de acordo e ciente dos termos de uso.",
        "required" => 1,
      ]],
      "type_field" => "selection_type",
      "name" => "terms",
    ],
    [
      "depth" => 1,
      "type" => "checkbox",
      "Options" => [[
        "value" => 1,
        "display" => "Desejo ficar por dentro das novidades e conteúdos do conquiste.me.",
      ]],
      "type_field" => "selection_type",
      "name" => "customer[lead]",
    ],
  ]
];

$checkout_fields[] = $payment_data;

/**
 *
 * Put the last row inside de the if you don't wanna save the user card.
 *
 */
// if (!empty($plan['sale_price']) && $plan['sale_price'] > 0) {
// }

/**
 *
 * Force plan_id (or the one_off flag) in form.
 *
 */
if (!empty($one_off_item))
{
  $checkout_fields[] = [
    "depth" => 1,
    "type_field" => "hidden",
    "name" => "one_off",
    'Value' => 1,
  ];
}
else
{
  $checkout_fields[] = [
    "depth" => 1,
    "pointer" => "plan_id",
    "type" => "GET",
    "type_field" => "hidden",
    "name" => "plan_id",
    'Value' => $plan_id,
  ];
}

$crud = [
  'attributes' => "id:(checkout-form);",
  "size" => "col-lg-8",
  "view_mode" => "steps_form",
  "without_reload" => true,
  "type_crud" => "insert",
  "form_settings" => [
    "view_mode" => "steps_form",
    "steps_form" => [
      "one_step_at_a_time" => 1,
      "show_progess" => 1,
      "show_steps" => 1,
      "progess_style" => "progress_steps_detailed",
      "progress_color" => "secondary",
      "button_name_send" => $is_one_off ? "Finalizar compra" : "Quero os benefícios"
    ]
  ],
  "form_action" => [
    "type" => "api",
    "action" => "process-order"
  ],
  "contents" => [
    "inputs" => $checkout_fields,
  ]
];

/**
 *
 * Timer
 *
 */
$final = time() + (15 * 60);
$timer = [
  'subtitle' => 'Oferta imperdível!',
  'align' => 'text-center',
  'class' => 'card-header',
  'final_moment' => [
    'date' => date('Y-m-d', $final),
    'time' => date('H:i', $final),
  ],
];

/**
 *
 * UI
 *
 */
include_once AREAS_PATH .'/app/include/head.php';
include_once AREAS_PATH .'/app/include/menu.php';
?>

<main class="pt-0 container <?= (!$is_checkout ? 'pay' : '') ?>" role="main">

<?php if ($is_checkout && $item_exists): ?>
<div class="row">

  <?php
  if (get_system_info('pyrosales_is_sandbox')) {
    echo "<div class='col-12'>". alert_message("IF_USING_SANDBOX", 'alert') ."</div>";
  }

  /**
   * Just show the form if:
   * - Sale price is = 0
   */
  if (count($checkout_fields) > 1) {
    echo form($crud);
  }
  ?>
  <aside class="col-lg-4 order-details">

    <div class="card">
    <?php
    // echo block('regressive_counter', $timer);
    ?>
    <div class="card-body">

      <div>
        <h2><?= $item_title ?></h2>
      </div>

      <?php if (!empty($one_off_item)): ?>
      <div class="one-off-quantity" data-one-off-cart
        data-unit-price="<?= (float) $one_off_item['unit_price'] ?>"
        data-regular-unit-price="<?= (float) $one_off_item['regular_unit_price'] ?>">
        <span>Quantidade</span>
        <div class="stepper">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-one-off-qty="decrease">-</button>
          <span data-one-off-qty-value><?= $item_qty ?></span>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-one-off-qty="increase">+</button>
        </div>
      </div>
      <?php endif; ?>

      <div class="values">
      <div class="value-row">
        <span>Total</span>
        <span data-checkout-total><?= "<{$tag}>". $currency($regular_amount) ."</{$tag}>" ?></span>
      </div>

      <?php if(!empty($discount)): ?>
      <div class="value-row" data-checkout-discount-row<?= empty($discount) ? ' style="display:none"' : '' ?>>
        <span>Você economiza</span>
        <span class="discount">
          <span data-checkout-discount-value>
            <?= $currency($discount) ." ({$discount_percent}%)" ?>
          </span>
          <?= $unit_display ?? '' ?>
        </span>
      </div>
      <?php endif;?>

      <hr>

      <?php
      $amount_display = (!($amount <= 0) ? $currency($amount) : 'GRATUITO');
      if ($is_one_off)
      {
        echo "
        <div class='value-row'>
          <span>Investimento</span>
          <span class='amount' data-checkout-amount>{$amount_display}</span>
        </div>";
      }

      elseif (!empty($plan))
      {
        echo "
        <div class='value-row'>
          <span>Investimento</span>
          <span class='amount' data-checkout-amount>{$amount_display}{$unit_display}</span>
        </div>";

        if ($plan['sale_price_cycles'] > 0)
        {
          $variation = ($plan['sale_price_cycles'] == 1)
            ? 'singular'
            : 'plural';

          echo "<small>{$plan['sale_price_cycles']} renovações com desconto de {$amount_display}{$unit_display} (valor normal ". $currency($regular_amount) .").</small>";
        }
      }
      ?>
      </div>

      <a href="#mobile-anchor" class="btn btn-primary btn-block">Resgatar benefícios</a>

      <p class="secure"><?= icon('fas fa-lock') ?> Compra 100% segura</p>

    </div>
    </div>

    <small>Este site é protegido pelo reCAPTCHA e o Google Privacidade & Termos e Termos de Serviço se aplicam.</small>
  </aside>



<span id='mobile-anchor'></span>

</div>
<?php elseif ($is_checkout && !$item_exists): ?>

  <div class="card hero">
  <div class="card-body">
    <div class="big-icon error"><?= icon('fas fa-x') ?></div>
    <h1><?php
      if ($is_one_off) {
        echo 'O produto que você escolheu não existe';
      } elseif (!empty($plan_id)) {
        echo 'O plano que você escolheu não existe';
      } else {
        echo 'Não há nenhum item no seu carrinho';
      }
    ?></h1>
  </div>
  </div>

<?php elseif (!$is_checkout && !empty($order)): ?>

  <?php
  $expires_at = strtotime($payments[0]['expires_at'] ?? '');
  $timer = [
    'subtitle' => 'PIX expira em:',
    'align' => 'text-center',
    'class' => 'card-header',
    'final_moment' => [
      'date' => date('Y-m-d', $expires_at),
      'time' => date('H:i', $expires_at),
    ],
  ];

  $checkout_page_id = get_system_info('checkout_page_id');
  $checkout_url     = get_url_page($checkout_page_id, 'full');

  // Plans can be bought again from a stable ?plan_id= link. One-off items
  // don't have that (no fixed DB row to point back to), so the "buy again"
  // link just sends the shopper back to the checkout entry point.
  $retry_url = $order_is_one_off ? $checkout_url : "{$checkout_url}?plan_id={$plan_id}";
  $retry_copy = $order_is_one_off
    ? "Não fique para trás perdendo sua compra, gere outro pagamento <a href=\"{$retry_url}\">clicando aqui</a>."
    : "Não fique para trás perdendo os benefícios do plano, gere outro pagamento <a href=\"{$retry_url}\">clicando aqui</a>.";
  ?>

  <?php if ($payments[0]['method'] == 'pix'): ?>
  <section class="pix" check-order-status="<?= $order_id ?>">

  <div class="card hero">
  <div class="card-body">
    <div class="big-icon success"><?= icon('fas fa-money-bill-wave') ?></div>
    <h1>Pague <?= $currency($payments[0]['amount']) ?> via Pix para <?= $order_is_one_off ? 'liberar sua compra' : 'ativar seu plano' ?></h1>
  </div>
  </div>

  <div class="card">
    <?= block('regressive_counter', $timer) ?>
  <div class="card-body">
  <div class="img-container">
    <img class="qrcode" src="<?= $payments[0]['payment_link'] ?>">
  </div>

  <ol class="desktop">
    <li>Acesse seu Internet Banking ou app de pagamentos</li>
    <li>Escolha pagar via Pix</li>
    <li>Escaneie o QRcode acima ou cole o código abaixo.</li>
  </ol>

  <ol class="mobile">
    <li>Acesse seu Internet Banking ou app de pagamentos.</li>
    <li>Escolha pagar via Pix</li>
    <li>Cole o código abaixo</li>
  </ol>

  <?= input('copy', 'insert', [
    'size' => 'col-12',
    'Value' => $payments[0]['code'] ?? null,
  ]) ?>

  <div class="disclaimer">
  <?php
  echo block('alert',
  [
    'body' => icon("fas fa-info-circle") .' Pague e liberaremos '. ($order_is_one_off ? 'sua compra' : 'os benefícios do seu plano') .' na hora',
    'variation' => 'alert-disclaimer',
    'close_button' => false,
    'color' => 'primary'
  ]);
  ?>
  </div>

  <!-- <a href="<?= site_url('/perfil') ?>" class="btn btn-link btn-block">Acessar minha conta</a> -->
  </div>

  </section>

  <section class="card hero expired-payment" style="display: none;">
  <div class="card-body">
    <div class="big-icon error"><?= icon('fas fa-x') ?></div>
    <h1>Poxa, seu pagamento expirou :(</h1>
    <p><?= $retry_copy ?></p>
  </div>
  </section>
  <?php endif; ?>

<?php else: ?>

  <section class="card hero">
  <div class="card-body">
    <div class="big-icon error"><?= icon('fas fa-x') ?></div>
    <h1>Não foi possível localizar este pedido ou ele não pertence a você.</h1>
  </div>
  </section>

<?php endif; ?>
</main>

<?php include_once AREAS_PATH .'/app/include/footer.php'; ?>
