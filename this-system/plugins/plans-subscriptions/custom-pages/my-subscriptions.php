<?php
if (!isset($seg)) exit;

/**
 * "Minhas assinaturas" -- customer-facing subscriptions self-service page.
 *
 * Same `/app` scaffold flavor as pyrosales' custom-pages/payment-methods.php
 * ($current_user scoped, theme-rendered head/menu/footer) -- see the note
 * left in install.php's page registration for this one.
 */

global $current_user;

add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/card-hero.css', 'url') ."'>");
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/pyrosales/assets/styles/payment-methods.css', 'url') ."'>");
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/plans-subscriptions/assets/styles/users-subscriptions.css', 'url') ."'>");
add_asset('head', "<link rel='stylesheet' href='". plugin_path('/plans-subscriptions/assets/styles/my-subscriptions.css', 'url') ."'>");
add_asset('footer', "<script defer src='" . plugin_path('/plans-subscriptions/assets/scripts/subscriptions.js', 'url') . "' defer></script>");
add_asset('footer', "<script defer src='" . plugin_path('/plans-subscriptions/assets/scripts/my-subscriptions.js', 'url') . "' defer></script>");
add_asset('footer', "<script defer src='" . plugin_path('/pyrosales/assets/scripts/user-payment-methods-input.js', 'url') . "' defer></script>");
feature('listings');


include_once AREAS_PATH .'/app/include/head.php';
include_once AREAS_PATH .'/app/include/menu.php';

$user_id = (int) ($current_user['id'] ?? 0);
?>

<main class="my-subscriptions-page" role="main">

<?php if ($user_id <= 0 || get_subscriptions('count', ['user_id' => $user_id]) === 0): ?>

    <div class="card hero">
    <div class="card-body">
        <div class="big-icon error"><?= icon('fas fa-x') ?></div>
        <h1>Você ainda não tem nenhuma assinatura</h1>
        <a class="btn btn-st" href="<?= site_url('/planos') ?>">Assinar um plano agora</a>
    </div>
    </div>

<?php elseif (get_system_info('subscriptions_business_model') == 'd2c_single_plan'): ?>
<section class="module">

    <?php
    $subscription = get_subscriptions('', [
        'user_id' => $user_id,
        'order_field' => 'id',
        'order_dir' => 'desc',
        'limit' => 1
    ])[0] ?? null;

    $plan = get_plan($subscription['plan_id'] ?? '');

    $card = get_result("
        SELECT
            brand_name, last4, exp_month, exp_year
        FROM tb_user_payment_methods
        WHERE id = '{$subscription['user_payment_method_id']}'");

    $status = (string) ($subscription['status'] ?? '');
    $recurrence = format_subscription_recurrence(
        (int) ($subscription['plan_interval_count'] ?? 1),
        (string) ($subscription['plan_interval_unit'] ?? 'month')
    );


    $days_subscribed = 0;
    if (!empty($subscription['started_at'])) {
        $started_ts = strtotime((string) $subscription['started_at']);
        if ($started_ts !== false) {
            $days_subscribed = max(0, (int) floor((strtotime('today') - $started_ts) / 86400));
        }
    }

    $tenure_formatter = function ($days) {
        $days = (int) $days;
        if ($days >= 365) {
            $years = intdiv($days, 365);
            return $years . ($years === 1 ? ' ano' : ' anos');
        }
        if ($days >= 30) {
            $months = intdiv($days, 30);
            return $months . ($months === 1 ? ' mês' : ' meses');
        }
        return $days . ($days === 1 ? ' dia' : ' dias');
    };

    $plan_ref   = !empty($subscription['plan_slug']) ? $subscription['plan_slug'] : (int) ($subscription['plan_id'] ?? 0);
    $checkout_page_id = get_system_info('checkout_page_id');
    $checkout_base    = $checkout_page_id ? get_url_page($checkout_page_id, 'full') : '/checkout';
    $renew_url  = $checkout_base . '?plan_id=' . urlencode((string) $plan_ref);
    $cancel_url = get_url_page('cancelar-assinatura', 'full') . '?id=' . (int) ($subscription['id'] ?? 0);
    $cancelable = in_array($status, ['active', 'trialing', 'past_due'], true);

    $actions = "<a href='{$renew_url}' class='btn btn-primary btn-sm'>" . icon('fas fa-rotate') . " Renovar agora</a>";
    if ($cancelable) {
        $actions .= "<a href='{$cancel_url}' class='btn btn-cancel btn-link btn-sm'>" . icon('fas fa-ban') . " Cancelar</a>";
    }

    $next_billing = $cancelable
        ? "<small>Sua assinatura será renovada em ". format_date_long_ptbr($subscription['next_billing_at'], false) .".</small>"
        : '';

    /**
     * Princing logic
     */
    $regular_amount = $plan['regular_price'];
    $currency = $plan['currency'] ?? DEFAULT_CURRENCY;
    $amount = ($plan['sale_price'] > 0)
        ? $plan['sale_price']
        : $plan['regular_price'];
    $amount_display = (!($amount <= 0) ? $currency($amount) : 'GRATUITO');

    $unit      = $plan['interval_unit'] ?? null;
    $count     = $plan['interval_count'] ?? null;
    $intervals = [
      'singular' => [ 'day' => 'dia', 'week' => 'semana', 'month' => 'mês', 'year' => 'ano', ],
      'plural' => [ 'day' => 'dias', 'week' => 'semanas', 'month' => 'mêses', 'year' => 'anos', ],
    ];

    $variation = ($count == 1)
      ? 'singular'
      : 'plural';

    $unit_display = (($count == 1) && !empty($intervals[$variation][$unit]))
      ? "<span class='interval-display'>/{$intervals['singular'][$unit]}</span>"
      : "<span class='interval-display-full'> a cada {$count} {$intervals[$variation][$unit]}</span>";

    /**
     * Discount logic
     */
    $discount = $discount_percent = '';
    if ($plan['sale_price'] > 0)
    {
        $tag = 's';
        $discount         = $regular_amount - $amount;
        $discount_percent = (($plan['regular_price'] - $plan['sale_price']) / $plan['regular_price']) * 100;
        $discount_percent = number_format($discount, 0);
    }

    $sale_price_display = '';
    if ($plan['sale_price_cycles'] > 0)
    {
        $variation = ($plan['sale_price_cycles'] == 1)
          ? 'singular'
          : 'plural';

        $cycle_discount_left = $plan['sale_price_cycles'] - $subscription['cycles_quantity'];
        $sale_price_display = "Você tem {$cycle_discount_left} renovações com desconto de {$amount_display}{$unit_display} (valor normal ". $currency($regular_amount) .").";
    }

    /**
     * Upgrade CTA
     */
    $upgrade_cta = ($plan['order_reg'] != 1)
        ? "<a href='". site_url('/planos') ."' class='btn btn-link btn-sm p-0'>Quero mais benefícios ". icon('icon fas fa-arrow-right'). "</a>"
        : '';


    $kpis = block('insight_card', [
        'size'      => 'col-6',
        'title'     => 'Assinante há',
        'data'      => $days_subscribed,
        'formatter' => $tenure_formatter,
        'color'     => 'white',
    ]);
    $kpis .= block('insight_card', [
        'size'      => 'col-6',
        'title' => 'Ciclos cobrados',
        'data'  => (int) ($subscription['cycles_quantity'] ?? 0),
        'color'     => 'white',
    ]);
    $kpis .= block('insight_card', [
        'size'      => 'col-12',
        'title' => 'Investimento',
        'text'  => render_subscription_price_html($subscription, $plan),
        'color'     => 'white',
        'small' => $sale_price_display,
    ]);


    echo "
    <section class='subscription-detail-card'>
    <div class='container'>
    <div class='form-row'>

        <article class='col-12'>
        <div class='about-plan'>
            <div class='details'>
            ". icon('fas fa-heart') ."
            <div>
                <div class='title'>
                    <h2>" . e((string) ($subscription['plan_name'] ?? '')) ."</h2> · ".
                    general_stats($status, 'subscription_status', 'button') . "
                </div>
                <p>{$recurrence}</p>
                {$next_billing}
            </div>
            </div>
            <div class='cta'>
                <a class='btn btn-st' href='". site_url('/planos') ."'>Ajustar plano</a>
            </div>
        </div>
        </article>

        <div class='col-12'>
        <h2>Pagamento</h2>
        <div class='plan-payment-method'>
            ".render_card_brand_html($card)."
            <button type='button' class='btn btn-st btn-sm' data-bs-toggle='modal' data-bs-target='#update-payment-modal'>Atualizar</button>
        </div>
        </div>

        <div class='col-md-6 benefits-container'>
        <div class='card'>
        <div class='card-body benefits'>
            <h2>Seus benefícios</h2>
            <p>". format_text(nl2br($plan['description'])) ."</p>
            {$upgrade_cta}
        </div>
        </div>
        </div>

        <div class='col-md-6 kpis-container'>
        <div class='form-row kpis'>{$kpis}</div>
        </div>

        <div class='col-12 actions-container'>
            <h2>Ações</h2>
            <div class='actions'>{$actions}</div>
        </div>

    </div>
    </div>
    </section>";

    /**
     * "Atualizar pagamento" modal -- edits statement_descriptor and the
     * payment method together in a single data-send-without-reload submit,
     * both landing on update-subscription-field (plans-subscriptions/
     * api.php), which now accepts either/both fields in one request and
     * re-checks ownership of the subscription AND the chosen card against
     * the logged-in user before saving anything. Rendered inline in this
     * page's own HTML (not fetched dynamically) -- only $cards already
     * active (list_user_saved_payment_methods()'s $only_active=true call
     * above) are offered, matching what a subscription can actually be
     * charged against.
     */
    echo block('modal', [
        'id'           => 'update-payment-modal',
        'title'        => 'Atualizar pagamento',
        'close_button' => true,
        'body'         =>
            "<form class='form-row' data-send-without-reload action='" . rest_api_route_url('update-subscription-field') . "' method='POST'>"
            . input('hidden', 'update', [
                'name'  => 'id',
                'Value' => $subscription['id'] ?? '',
            ])
            . input('hidden', 'update', [
                'name'  => 'reload_on_success',
                'Value' => 1,
            ])
            . input('basic', 'update', [
                'size'       => 'col-12',
                'label'      => 'Nome na fatura',
                'name'       => 'statement_descriptor',
                'attributes' => 'maxlength:(20);',
                'Value'      => $subscription['statement_descriptor'] ?? '',
            ])
            . input('user_payment_methods', 'update', [
                'size'    => 'col-12',
                'label'   => 'Meio de pagamento',
                'name'    => 'user_payment_method_id',
                // 'attributes' => "data-subscription-id:({$subscription['id']});",
                'show_default' => true,
                'Options' => list_user_saved_payment_methods($user_id, true),
                'Value'   => $subscription['user_payment_method_id'] ?? '',
            ])
            . input('submit_button', 'update', [
                'size'  => 'col-12',
                'class' => 'btn btn-st',
                'block' => true,
                'Value' => 'Salvar',
            ])
            . "</form>

            <p class='my-cards'>Seu meio de pagamento desejado não apareceu? Gerencie suas métodos <a href='". site_url('/meus-cartões') ."'>clicando aqui.</a></p>",
    ]);
    ?>
</section>

<?php else: ?>
<section class="module">
<div class="container">
    <?=
    custom_listing_table('plugin/pyrosales', 'my-commissions', [
      'title'          => 'Minhas assinaturas',
      'listing_params' => ['user_id' => $user_id],
    ]) ?>
</div>
</section>

<?php endif; ?>

</main>

<?php include_once AREAS_PATH .'/app/include/footer.php'; ?>
