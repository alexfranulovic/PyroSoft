<?php
if (!isset($seg)) exit;

/**
 * render_plans_segment_tables()
 *
 * Queries the distinct (target_audience, interval_count, interval_unit)
 * combinations among D2C plans (user_id IS NULL), buckets them by
 * target_audience, and renders one navtabs() group — one tab per audience,
 * each tab holding that audience's period tables (one
 * custom_listing_table('plugin/pyrosales', 'plans', ...) per
 * interval_count/interval_unit group, same listing config reused every
 * time via `listing_params` — see plans.php's function_name closure, which
 * reads these back as `$args['extra']`).
 *
 * Audience tab order: FIELD(target_audience, 'escort', 'customer') —
 * matches your example (escort before customer). Add any other audience
 * values to that list in priority order; anything not listed falls back to
 * alphabetical, after the ones that are.
 */
function render_plans_segment_tables(): string
{
    $groups = get_results("
        SELECT DISTINCT target_audience, interval_count, interval_unit
        FROM tb_plans
        WHERE user_id IS NULL
        ORDER BY FIELD(target_audience, 'escort', 'customer') ASC,
                 target_audience ASC,
                 FIELD(interval_unit, 'day', 'week', 'month', 'year', 'lifetime') ASC,
                 interval_count ASC
    ");

    if (empty($groups)) {
        return '<p>No plan found.</p>';
    }


    // Bucket by target_audience, preserving the SQL ORDER BY sequence —
    // that's what decides both tab order and the order of tables inside
    // each tab.
    $by_audience = [];
    foreach ($groups as $group) {
        $by_audience[$group['target_audience']][] = $group;
    }

    $tabs  = [];
    $first = true;

    foreach ($by_audience as $audience => $audience_groups) {
        $body = '';

        foreach ($audience_groups as $group)
        {
            $body .= custom_listing_table('plugin/plans-subscriptions', 'plans', [
                'title'          => "{$group['interval_count']} {$group['interval_unit']}",
                'listing_params' => [
                    'target_audience' => $group['target_audience'],
                    'interval_count'  => (int) $group['interval_count'],
                    'interval_unit'   => $group['interval_unit'],
                ],
            ]);
        }

        $tabs[] = [
            'title'  => $audience,
            'body'   => $body,
            'active' => $first,
        ];
        $first = false;
    }

    return block('navtabs', [
        'id'        => 'plans-audience-tabs',
        'variation' => 'navtabs_default',
        'contents'  => $tabs,
    ]);
}


/**
 * Computes what a subscription's current/next charge amount is, mirroring
 * create_subscription_renewal_order()'s own amount formula exactly
 * (pyrosales, src/subscriptions/renewal.php) -- this is display-only (the
 * "preço plano" column on both subscriptions listings), so it must never
 * diverge from what actually gets billed.
 *
 * Note: like the real renewal formula, a plan with no sale_price set AND
 * sale_price_cycles = 0 (the "always apply the discount" default) will
 * compute to 0 here too -- that mirrors a real ER on renewal, not a display
 * bug; give such plans a sale_price (or a non-zero sale_price_cycles) if
 * that shows up.
 *
 * @param array $subscription tb_plan_user_subscriptions row (needs at least id, cycles_quantity).
 * @param array $plan         tb_plans row (needs sale_price, sale_price_cycles, regular_price, currency).
 *
 * @return array{amount:float,is_discounted:bool,currency:string}
 */
function calculate_subscription_price(array $subscription, array $plan): array
{
    if (empty($plan)) {
        return ['amount' => 0.0, 'is_discounted' => false, 'currency' => 'BRL'];
    }

    $proposal = (!empty($subscription['id']) && function_exists('get_subscription_proposal'))
        ? get_subscription_proposal((int) $subscription['id'])
        : [];

    $sale_price_cycles = (int) ($plan['sale_price_cycles'] ?? 0);
    $cycles_quantity    = (int) ($subscription['cycles_quantity'] ?? 0);

    $amount        = (float) ($proposal['sale_price'] ?? $plan['sale_price']);
    $is_discounted = true;

    if ($cycles_quantity > $sale_price_cycles) {
        $amount        = (float) ($proposal['regular_price'] ?? $plan['regular_price']);
        $is_discounted = false;
    }

    return [
        'amount'        => $amount,
        'is_discounted' => $is_discounted,
        'currency'      => (string) ($plan['currency'] ?? 'BRL'),
    ];
}

/**
 * Renders the "preço plano" cell used by both subscriptions listings --
 * amount + a "Com desconto"/"Sem desconto" badge (see calculate_subscription_price()).
 */
function render_subscription_price_html(array $subscription, array $plan): string
{
    $info      = calculate_subscription_price($subscription, $plan);

    $currency      = e((string)($info['currency'] ?? DEFAULT_CURRENCY));
    $regular_price = $currency((float) $plan['regular_price'] ?? 0);
    $sale_price    = $currency((float) $plan['sale_price'] ?? 0);

    return $info['is_discounted']
        ?"<s>{$regular_price}</s> {$sale_price}"
        : $sale_price;
}

/**
 * "Renovação automática" switch cell, shared by both subscriptions
 * listings -- toggled via the `toggle-subscription-auto-renew` REST route
 * (api.php), picked up by assets/scripts/subscriptions.js.
 */
function render_subscription_auto_renew_switch_html(array $subscription): string
{
    $id      = (int) ($subscription['id'] ?? 0);
    $checked = !empty($subscription['auto_renew']);

    return input('selection_type', 'update', [
        'type' => 'switch',
        'name' => 'auto_renew',
        'Options' => [
            [
                'value' => '1',
                'display' => '',
                'attributes' => "data-toggle-subscription-auto-renew:({$id});",
                'checked' => $checked,
            ]
        ],
    ]);
}

/**
 * Admin "Botões" column -- Cobrar/pausar/cancelar. Cobrar/pausar stay
 * visible but disabled (a separate, later task); cancelar is now wired to
 * `cancel-subscription-admin` (api.php + assets/scripts/subscriptions.js) --
 * an immediate cancellation (unlike the customer-facing flow), only enabled
 * while the subscription is in a status that can actually be canceled.
 */
function render_subscription_admin_actions_html(array $subscription): string
{
    $id             = (int) ($subscription['id'] ?? 0);
    $status         = (string) ($subscription['status'] ?? '');
    $cancelable     = in_array($status, ['pending', 'active', 'trialing', 'past_due', 'paused'], true);
    $cancel_attrs   = $cancelable
        ? "data-subscription-action='cancel' data-subscription-id='{$id}'"
        : "disabled title='Assinatura não pode ser cancelada no status atual'";

    return "
    <div class='btn-group btn-group-sm' role='group' aria-label='Ações da assinatura'>
        <button type='button' class='btn btn-outline-success' disabled title='Em breve' data-subscription-action='charge' data-subscription-id='{$id}'>" . icon('fas fa-dollar-sign') . "</button>
        <button type='button' class='btn btn-outline-warning' disabled title='Em breve' data-subscription-action='pause' data-subscription-id='{$id}'>" . icon('fas fa-pause') . "</button>
        <button type='button' class='btn btn-outline-danger' {$cancel_attrs}>" . icon('fas fa-ban') . "</button>
    </div>";
}

/**
 * Customer "Botões" column -- renovar/pausar/cancelar.
 *
 * renovar/cancelar are now real links (renovar -> checkout for this plan,
 * cancelar -> the cancellation screen); pausar stays a visible-but-disabled
 * placeholder, same "later task" treatment as render_subscription_admin_actions_html().
 */
function render_subscription_customer_actions_html(array $subscription): string
{
    $id       = (int) ($subscription['id'] ?? 0);
    $plan_ref = !empty($subscription['plan_slug']) ? $subscription['plan_slug'] : (int) ($subscription['plan_id'] ?? 0);

    $checkout_page_id = get_system_info('checkout_page_id');
    $checkout_base    = $checkout_page_id ? get_url_page($checkout_page_id, 'full') : '/checkout';
    $renew_url        = $checkout_base . '?plan_id=' . urlencode((string) $plan_ref);
    $cancel_url       = get_url_page('cancel-subscription', 'full') . '?id=' . $id;

    return "
    <div class='btn-group btn-group-sm' role='group' aria-label='Ações da assinatura'>
        <a href='{$renew_url}' class='btn btn-outline-primary' title='Renovar' data-subscription-action='renew' data-subscription-id='{$id}'>" . icon('fas fa-rotate') . "</a>
        <a href='{$cancel_url}' class='btn btn-outline-danger' title='Cancelar' data-subscription-action='cancel' data-subscription-id='{$id}'>" . icon('fas fa-ban') . "</a>
    </div>";
}

/**
 * "Recorrência" column (customer listing) -- e.g. "A cada mês", "A cada 3
 * meses", "A cada ano", "Vitalício".
 */
function format_subscription_recurrence(int $interval_count, string $interval_unit): string
{
    if ($interval_unit === 'lifetime') {
        return 'Vitalício';
    }

    $units = [
        'day'   => ['dia', 'dias'],
        'week'  => ['semana', 'semanas'],
        'month' => ['mês', 'meses'],
        'year'  => ['ano', 'anos'],
    ];

    [$singular, $plural] = $units[$interval_unit] ?? [$interval_unit, $interval_unit];
    $label = $interval_count === 1 ? $singular : "{$interval_count} {$plural}";

    return "A cada {$label}";
}

/**
 * Canonical list of cancellation-reason categories offered on the
 * customer's cancellation form's first radio group
 * (custom-pages/cancel-subscription.php) -- also the source of truth for
 * turning a stored `cancel_reason` value back into a human label wherever
 * a canceled subscription's reason is displayed (e.g. the timeline, the
 * admin subscription-manager form).
 */
function get_subscription_cancel_reason_options(): array
{
    return [
        ['value' => 'preco',        'display' => 'O preço está muito alto'],
        ['value' => 'nao_uso',      'display' => 'Não estou usando o suficiente'],
        ['value' => 'concorrente',  'display' => 'Encontrei uma alternativa melhor'],
        ['value' => 'tecnico',      'display' => 'Tive problemas técnicos'],
        ['value' => 'atendimento',  'display' => 'Atendimento não atendeu minhas expectativas'],
        ['value' => 'outro',        'display' => 'Outro motivo'],
    ];
}

/**
 * Canonical list of "how likely are you to come back" options offered on
 * the same form's second radio group -- see
 * get_subscription_cancel_reason_options() above for the same idea applied
 * to the stored `probability_return` value.
 */
function get_subscription_return_likelihood_options(): array
{
    return [
        ['value' => 'extremamente_provavel',   'display' => 'Extremamente provável'],
        ['value' => 'provavel',                'display' => 'Provável'],
        ['value' => 'neutro',                  'display' => 'Neutro'],
        ['value' => 'improvavel',              'display' => 'Improvável'],
        ['value' => 'extremamente_improvavel', 'display' => 'Extremamente improvável'],
    ];
}

/**
 * Turns a stored option value back into its human label, using
 * $options' ['value' => ..., 'display' => ...] shape (see the two
 * functions above) -- falls back to the raw value when it doesn't match
 * any option, and to '' when $value itself is empty.
 */
function subscription_option_label(array $options, ?string $value): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    foreach ($options as $option) {
        if (($option['value'] ?? null) === $value) {
            return (string) ($option['display'] ?? $value);
        }
    }

    return $value;
}

/**
 * Renders a visual timeline of every subscription a user has ever had
 * (tb_plan_user_subscriptions, all statuses, oldest first) -- their whole
 * subscription journey on the platform: when each one started, what they
 * paid, and how/when it ended.
 *
 * Only meaningful under the 'd2c_single_plan' business model, where a user
 * has one subscription lineage over time (upgrades/downgrades/renewals
 * finalize the old row and create a new one -- see subscription_business_logic(),
 * pyrosales/src/subscriptions/index.php) -- under the other business models
 * a user can hold several unrelated subscriptions at once, which isn't a
 * single "journey" to plot on one line, so this returns an explanatory
 * message instead of a misleading timeline in that case.
 *
 * @param int $user_id
 *
 * @return string
 */
function render_user_plans_timeline_html(int $user_id): string
{
    if (get_system_info('subscriptions_business_model') != 'd2c_single_plan') {
        return "<p class='text-muted'>Esta timeline só está disponível quando o modelo de negócio das assinaturas é 'd2c_single_plan'.</p>";
    }

    if ($user_id <= 0) {
        return "<p class='text-muted'>Usuário inválido.</p>";
    }

    $subscriptions = get_results("
        SELECT
            sub.*,
            p.name              AS plan_name,
            p.currency          AS plan_currency,
            p.regular_price     AS plan_regular_price,
            p.sale_price        AS plan_sale_price,
            p.sale_price_cycles AS plan_sale_price_cycles
        FROM tb_plan_user_subscriptions AS sub
        INNER JOIN tb_plans AS p ON p.id = sub.plan_id
        WHERE sub.user_id = '{$user_id}'
        ORDER BY sub.id DESC
    ");

    if (empty($subscriptions)) {
        return "<p class='text-muted'>Este usuário ainda não teve nenhuma assinatura.</p>";
    }

    $items = '';
    $count = count($subscriptions);

    foreach ($subscriptions as $i => $sub)
    {
        $footer = '';
        $plan = [
            'currency'          => $sub['plan_currency'] ?? 'BRL',
            'regular_price'     => $sub['plan_regular_price'] ?? 0,
            'sale_price'        => $sub['plan_sale_price'] ?? null,
            'sale_price_cycles' => $sub['plan_sale_price_cycles'] ?? 0,
        ];

        if (!empty($sub['canceled_at']))
        {
            $cancel_reason_label     = subscription_option_label(get_subscription_cancel_reason_options(), $sub['cancel_reason'] ?? null);
            $return_likelihood_label = subscription_option_label(get_subscription_return_likelihood_options(), $sub['probability_return'] ?? null);

            $footer = "
            <div class='text-muted small mt-2 border-top pt-2'>
                <div>" . icon('fas fa-ban') . ' Cancelada em ' . e((string) format_date($sub['canceled_at'])) . "</div>";

            if (!empty($sub['cancel_flow'])) {
                $footer .= "<div><strong>Origem</strong>: " . e((string) $sub['cancel_flow']) . "</div>";
            }
            if ($cancel_reason_label !== '') {
                $footer .= "<div><strong>Motivo</strong>: " . e($cancel_reason_label) . "</div>";
            }
            if (!empty($sub['user_cancel_detail'])) {
                $footer .= "<div><strong>Comentário</strong>: " . e((string) $sub['user_cancel_detail']) . "</div>";
            }
            if ($return_likelihood_label !== '') {
                $footer .= "<div><strong>Probabilidade de voltar</strong>: " . e($return_likelihood_label) . "</div>";
            }

            $footer .= "</div>";
        }

        elseif (!empty($sub['ended_at']))
        {
            $footer = "
            <div class='text-muted small mt-2'>
                " . icon('fas fa-flag-checkered') . ' Encerrada em ' . e((string) format_date($sub['ended_at'])) . '
            </div>';
        }

        $is_last = ($i === $count - 1);

        $items .= "
        <div class='timeline-item" . ($is_last ? ' timeline-item-last' : '') . "'>
            <div class='timeline-marker'>" . icon('fas fa-calendar-check') . "</div>
            <div class='card mb-4'>
            <div class='card-body'>
                <div class='d-flex justify-content-between align-items-start flex-wrap gap-2'>
                    <h3 class='h6 mb-1'>
                    <a title='Ver plano' href='". site_url('/admin/plan-manager?id='. $sub['plan_id']) ."'>" . e((string) $sub['plan_name']) . "</a>
                    </h3>
                    <div class='d-flex align-items-center gap-2'>
                        " . general_stats($sub['status'], 'subscription_status', 'button') . "
                        <a href='". site_url('/admin/subscription-manager?id=' . (int) $sub['id']) ."' class='btn btn-sm btn-outline-secondary' title='Ver assinatura'>" . icon('fas fa-eye') . "</a>
                    </div>
                </div>
                <div>" . render_subscription_price_html($sub, $plan) . " &middot; " . (int) $sub['cycles_quantity'] . " ciclo(s)</div>
                <p class='text-muted small'>Iniciada em " . e((string) format_date($sub['started_at'] ?? '-')) . "</p>
                <p class='text-muted small mt-0 mb-2'>Termina em " . e((string) format_date($sub['next_billing_at'] ?? '-')) . "</p>
                {$footer}
            </div>
            </div>
        </div>";
    }

    return "<div class='user-plans-timeline'>{$items}</div>";
}

/**
 * "Who gets charged" for one subscription is now rendered with pyrosales'
 * own `user_payment_methods` input (plugins/pyrosales/inputs/
 * user_payment_methods) -- see custom-listings/my-subscriptions.php and
 * custom-pages/my-subscriptions.php. This dropdown-look-alike (button +
 * hidden input, `data-subscription-id` optional via `attributes`) replaced
 * this function's own hand-rolled markup; its dedicated behavior lives in
 * pyrosales' assets/scripts/user-payment-methods-input.js.
 */
