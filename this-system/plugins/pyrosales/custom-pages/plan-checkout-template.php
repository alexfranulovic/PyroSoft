<?php
if (!isset($seg)) exit;

add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/checkout.css', 'url') ."'></script>");
add_asset('footer', "<script src='". plugin_path('/pyrosales/assets/scripts/pyrosales.js', 'url') ."' defer></script>");

include AREAS_PATH ."/app/include/head.php";
include AREAS_PATH ."/app/include/menu.php";

$plan_id = $_GET['plan_id'] ?? 0;

$plan = get_plan($plan_id);
// dump($plan);

$currency = $plan['currency'] ?? DEFAULT_CURRENCY;
if (!is_null($plan_id)) {
}
?>

<main class="pt-0 container" role="main" style="/*margin-top: 58px;*/">
<div class="row">


  <?php
  $customer = [
    "title" => "Dados pessoais",
    // "icon" => "fas fa-id-badge",
    "type_field" => "divider",
    'description' => "<p>Os seus dados de pagamento são criptografados e processados de forma segura.</p>",
    "depth" => 0,
    "childs" => [
      [
        "depth" => 1,
        "pointer" => "plan_id",
        "type" => "GET",
        "type_field" => "hidden",
        "name" => "plan_id",
        'Value' => $plan_id,
      ],
      [
        "depth" => 1,
        'size' => 'col-12',
        "label" => "Nome completo",
        "Required" => 1,
        "type_field" => "basic",
        "name" => "customer[name]",
      ],
      [
        "depth" => 1,
        "label" => "E-mail",
        "type" => "email",
        "function_proccess" => "auto_fill_name_by_cpf([email])",
        "attachment" => [
          "append" => "@email.com"
        ],
        "Required" => 1,
        "type_field" => "basic",
        "name" => "customer[email]",
      ],
      [
        "depth" => 1,
        "label" => "Senha",
        // "type" => "new-password",
        "type_field" => "password",
        "name" => "customer[password]",
        "Required" => 1,
      ],
      [
        "depth" => 1,
        "label" => "Telefone",
        'class' => 'mask-phone',
        "type_field" => "basic",
        "name" => "customer[phone]",
        "Required" => 1,
      ],
      [
        "depth" => 1,
        "label" => "CPF",
        'class' => 'mask-cpf',
        "type_field" => "basic",
        "name" => "customer[document_number]",
        "Required" => 1,
      ],
      [
        "depth" => 1,
        "type_field" => "hidden",
        "name" => "customer[document_type]",
        'Value' => 'CPF',
      ],
    ]
  ];
  $inputs[] = $customer;

  $payment_data = [
    "title" => "Meio de pagamento",
    // "icon" => "fas fa-money-bill",
    "type_field" => "divider",
    "depth" => 0,
    "childs" => [
      [
        "depth" => 1,
        "type_field" => "payment_methods",
      ],
      [
        "depth" => 1,
        "type" => "checkbox",
        "Options" => [[
          "value" => 1,
          "display" => "Ao me tornar membro(a) eu declaro estar de acordo e ciente dos termos de uso.",
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

  if ($plan['sale_price'] > 0) {
    $inputs[] = $payment_data;
  }

  $end = [
    "title" => "Resgate",
    "icon" => "fas fa-money-bill",
    "type_field" => "divider",
    "depth" => 0,
    "childs" => [
      [
        "depth" => 1,
        "size" => "col-sm-12 col-md-12 col-xxl-12",
        "type_field" => "shortcode",
      ]
    ]
  ];
  // $inputs[] = $end;

  $crud = [
    "size" => "col-lg-8",
    "view_mode" => "steps_form",
    "without_reload" => true,
    "type_crud" => "insert",
    "form_settings" => [
      "without_reload" => 1,
      "view_mode" => "steps_form",
      "steps_form" => [
        "one_step_at_a_time" => 1,
        "show_progess" => 1,
        "show_steps" => 1,
        "progess_style" => "progress_steps_detailed",
        "progress_color" => "secondary",
        "button_name_send" => "Quero os benefícios"
      ]
    ],
    "form_action" => [
      "type" => "api",
      "action" => "process-order"
    ],
    "contents" => [
      "inputs" => $inputs,
    ]
  ];

  echo form($crud);
  ?>

  <aside class="col-lg-4 order-details">

    <div class="card">
    <?php
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
    echo block('regressive_counter', $timer);
    ?>
    <div class="card-body">

      <div>
        <h2><?= $plan['name'] ?></h2>
      </div>

      <div class="values">
      <div class="value-row">
        <span>Total</span>
        <span><?= $currency($plan['regular_price']) ?></span>
      </div>

      <div class="value-row">
        <span>Desconto</span>
        <span><?= $currency(($plan['regular_price'] - $plan['sale_price'])) ?></span>
      </div>

      <hr>

      <div class="value-row">
        <span>Valor</span>
        <span class="amount"><?= $currency($plan['sale_price']) ?></span>
      </div>
      </div>

      <small>Este site é protegido pelo reCAPTCHA e o Google Privacidade & Termos e Termos de Serviço se aplicam.</small>

      <p class="secure"><?= icon('fas fa-lock') ?> Compra 100% segura</p>

    </div>
    </div>

  </aside>

</div>
</main>

<?php include AREAS_PATH ."/app/include/footer.php"; ?>
