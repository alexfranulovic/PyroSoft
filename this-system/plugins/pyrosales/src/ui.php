<?php
if (!isset($seg)) exit;

function build_order_total(array $order = [])
{
    $currency = $order['currency'] ?? DEFAULT_CURRENCY;

    if (empty($order)) return '';

    $res = "
    <div class='build-order-total'>
        <p>
            <span>Subtotal amount:</span>
            <bdi>{$currency($order['subtotal_amount'])}</bdi>
        </p>";

        if ($order['discount_amount'] > 0)
        {
            $res.= "
            <p>
                <span>Discount(s):</span>
                <bdi>-{$currency($order['discount_amount'])}</bdi>
            </p>";
        }

        if ($order['fee_amount'] > 0)
        {
            $res.= "
            <p>
                <span>Fee(s):</span>
                <bdi>{$currency($order['fee_amount'])}</bdi>
            </p>";
        }

        if ($order['shipping_amount'] > 0)
        {
            $res.= "
            <p>
                <span>Shipping:</span>
                <bdi>{$currency($order['shipping_amount'])}</bdi>
            </p>";
        }

        if ($order['tax_amount'] > 0)
        {
            $res.= "
            <p>
                <span>Tax(s):</span>
                <bdi>{$currency($order['tax_amount'])}</bdi>
            </p>";
        }

        $res.= "
        <hr>
        <p>
            <span>Total amount:</span>
            <bdi>{$currency($order['total_amount'])}</bdi>
        </p>";

        if ($order['gateway_fee'] > 0)
        {
            $res.= "
            <p>
                <span>Gateway fee:</span>
                <bdi>-{$currency($order['gateway_fee'])}</bdi>
            </p>";
        }

        if ($order['net_amount'] > 0)
        {

            $res.= "
            <hr>
            <p>
                <span>Net amount:</span>
                <bdi>{$currency($order['net_amount'])}</bdi>
            </p>";
        }

        if ($order['refunded_amount'] > 0)
        {
            $res.= "
            <hr>
            <p>
                <span>Refunded:</span>
                <bdi>-{$currency($order['refunded_amount'])}</bdi>
            </p>";
        }

        $res.= "
    </div>";

    return $res;
}


function order_customer_data(array $order = [])
{
    $document_type = $order['customer_document_type'] ?? '';
    $res = "
    <div class='subject-data-list'>";

        $res.= "<h3>Customer data</h3>";

        $res.= "
        <p>". icon('fas fa-user') ." <bdi>{$order['customer_first_name']} {$order['customer_last_name']}</bdi></p>";

        $res.= "
        <p>". icon('fas fa-envelope') ." <a href='mailto:{$order['customer_email']}'><bdi>{$order['customer_email']}</bdi></a></p>";

        $res.= "
        <p>". icon('fas fa-phone') ." <a href='tel:{$order['customer_phone']}'><bdi>{$order['customer_phone']}</bdi></a></p>";

        $res.= "
        <p>". icon('fab fa-whatsapp') ." <a target='_blank' href='http://wa.me/{$order['customer_phone']}'><bdi>{$order['customer_phone']}</bdi></a></p>";

        $res.= "
        <p>". icon('fas fa-id-badge') ." {$document_type}: <bdi>". $document_type($order['customer_document_number'])."</bdi></p>";

    $res .= "</div>";

    return $res;
}

function order_vendor_data(array $order = [])
{
    $document_type    = $order['vendor_document_type'] ?? '';
    $vendor_last_name =  $order['vendor_last_name'] ?? '';

    $res = "
    <div class='subject-data-list'>";

        $res.= "<h3>Vendor data</h3>";

        $res.= "
        <p>". icon('fas fa-user') ." <bdi>{$order['vendor_first_name']} {$vendor_last_name}</bdi></p>";

        if (!empty($order['vendor_phone']))
        {
            $res.= "
            <p>". icon('fas fa-envelope') ." <a href='mailto:{$order['vendor_email']}'><bdi>{$order['vendor_email']}</bdi></a></p>";
        }
        if (!empty($order['vendor_phone']))
        {
            $res.= "
            <p>". icon('fas fa-phone') ." <a href='tel:{$order['vendor_phone']}'><bdi>{$order['vendor_phone']}</bdi></a></p>";

            $res.= "
            <p>". icon('fab fa-whatsapp') ." <a target='_blank' href='http://wa.me/{$order['vendor_phone']}'><bdi>{$order['vendor_phone']}</bdi></a></p>";
        }
        if (!empty($document_type) && !empty($order['vendor_document_number']))
        {
            $res.= "
            <p>". icon('fas fa-id-badge') ." {$document_type}: <bdi>". $document_type($order['vendor_document_number'])."</bdi></p>";
        }

        /**
         * Commission snapshot: amount frozen on the order, current
         * settlement state (commission_status_id) and the hook that
         * releases it (commission_activation_function). See
         * resolve_order_commission_status() (src/status.php).
         */
        $commission_status = $order['commission_status_id'] ?? 'pending';
        $res.= "
        <p>". icon('fas fa-hand-holding-dollar') ." Comissão: <bdi>". general_stats($commission_status, 'vendor_status') ."</bdi></p>";

        if (isset($order['commission_amount']) && $order['commission_amount'] !== null && $order['commission_amount'] !== '')
        {
            $currency = $order['currency'] ?? DEFAULT_CURRENCY;
            $res.= "
            <p>". icon('fas fa-money-bill-wave') ." Valor: <bdi>". $currency((float) $order['commission_amount']) ."</bdi></p>";
        }

    $res .= "</div>";

    return $res;
}


function payment_details(array $payment = [])
{
    $currency = $payment['currency'] ?? DEFAULT_CURRENCY;

    $res = "
    <div class='payment-details form-row'>";

        // $res.= "
        // <div class='info col-sm-12'>
        //     <h3>Customer data</h3>
        // </div>";

        $res.= "
        <div class='info col-sm-6'>
            <h4>Status:</h4>
            <p>". general_stats($payment['status_id'], 'payment_status') ."</p>
        </div>";

        $res.= "
        <div class='info col-sm-6'>
            <h4>Currency:</h4>
            <p>{$currency}</p>
        </div>";


        $res.= "
        <div class='info col-sm-12'>
            <hr>
        </div>";


        $res.= "
        <div class='info col-sm-6'>
            <h4>Amount:</h4>
            <p>{$currency((float) $payment['amount'])}</p>
        </div>";

        if (!empty($payment['gateway_fee']))
        {
            $res.= "
            <div class='info col-sm-6'>
                <h4>Gateway fee:</h4>
                <p>{$currency((float) $payment['gateway_fee'])}</p>
            </div>";
        }

        if (!empty($payment['net_amount']))
        {
            $res.= "
            <div class='info col-sm-6'>
                <h4>Net amount:</h4>
                <p>{$currency((float) $payment['net_amount'])}</p>
            </div>";
        }

        if (!empty($payment['refunded_amount']))
        {
            $res.= "
            <div class='info col-sm-6'>
                <h4>Refunded amount:</h4>
                <p>{$currency((float) $payment['refunded_amount'])}</p>
            </div>";
        }

        if (!empty($payment['installments']))
        {
            $res.= "
            <div class='info col-sm-6'>
                <h4>Installments:</h4>
                <p>{$payment['installments']}x {$currency((float) $payment['installment_amount'])}</p>
            </div>";
        }

        if (!empty($payment['installments_fee_amount']))
        {
            $res.= "
            <div class='info col-sm-6'>
                <h4>Installments fee amount:</h4>
                <p>{$currency((float) $payment['installments_fee_amount'])}</p>
            </div>";
        }

        if (!empty($payment['installments_rate_amount']))
        {
            $res.= "
            <div class='info col-sm-6'>
                <h4>Installments rate amount:</h4>
                <p>{$currency((float) $payment['installments_rate_amount'])}</p>
            </div>";
        }

        $res.= "
        <div class='info col-sm-12'>
            <hr>
        </div>";


        // Card payments (credit_card/debit_card) show the saved card's own
        // brand/last4/expiration formatting -- render_card_brand_html()
        // needs the tb_user_payment_methods row itself (tb_order_payments
        // only carries the FK), so it's looked up here; anything without a
        // linked card (boleto, pix, or a card charged without ever being
        // saved) falls back to the raw method string, same as before.
        $card = !empty($payment['user_payment_method_id'])
            ? get_result("SELECT * FROM tb_user_payment_methods WHERE id = '{$payment['user_payment_method_id']}' LIMIT 1")
            : null;

        $method_html = !empty($card)
            ? render_card_brand_html($card)
            : e((string)($payment['method'] ?? ''));

        $res.= "
        <div class='info col-sm-6'>
            <h4>Method:</h4>
            <p>{$payment['method']}: {$method_html}</p>
        </div>";

        $res.= "
        <div class='info col-sm-6'>
            <h4>Provider:</h4>
            <p>{$payment['provider']}</p>
        </div>";

        $res.= "
        <div class='info col-sm-12'>
            <hr>
        </div>";

        if (!empty($payment['payment_link']))
        {
            $res.= "
            <div class='info col-sm-6'>
                <h4>Payment link:</h4>
                <p><a href='{$payment['payment_link']}'>{$payment['payment_link']}</a></p>
            </div>";
        }


        // if (!empty($payment['raw_response_json']))
        // {
        //     $res.= "
        //     <div class='info col-sm-12'>
        //         <h4>Provider response:</h4>
        //         <pre><code>". json_encode($payment['raw_response_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ."</code></pre>
        //     </div>";
        // }

        // Load the payment method an it settings.
        $payment_gateways = $GLOBALS['payment_gateways'];
        $payment_method_key = "{$payment['provider']}.{$payment['method']}";
        $payment_method   = $payment_gateways[$payment_method_key] ?? [];

        $refunded_amount  = (float) ($payment['refunded_amount'] ?? 0);
        $remaining_amount = (float) ($payment['amount'] ?? 0) - $refunded_amount;

        if ($payment_method['refundable_api'] && $remaining_amount > 0)
        {
            $res.= "
            <div class='info col-sm-12'>
                <button
                    type='button'
                    class='btn btn-danger btn-sm'
                    data-bs-toggle='collapse'
                    data-bs-target='#cancel-refund-{$payment['id']}'
                >
                    Cancel / Refund
                </button>

                <div class='collapse mt-3' id='cancel-refund-{$payment['id']}'>
                <div class='card' data-cancel-payment-id='{$payment['id']}' data-max-amount='{$remaining_amount}'>
                <div class='card-body'>

                     <p class='mb-1'><strong>Remaining refundable amount:</strong> {$currency($remaining_amount)}</p>
                     <small class='text-muted d-block mb-3'>
                         Full cancels/refunds the entire remaining balance. Partial lets you specify a custom amount, up to the remaining balance above.
                     </small>

                     <form method='POST' class='form-row' data-send-without-reload action=". rest_api_route_url("cancel-order")." >".
                        input('hidden', 'insert', [
                            'name' => 'payment_id',
                            'Value' => $payment['id']
                        ]). input(
                            'selection_type',
                            'insert',
                            [
                                'type' => 'radio',
                                'size' => 'col-12',
                                'name' => "refund-mode",
                                'variation' => 'btn-group',
                                'Options' => [
                                    ['value' => 'full', 'display' => 'Full'],
                                    ['value' => 'partial', 'display' => 'Partial'],
                                ],
                                'Value' => 'full',
                                'Required' => true
                            ]
                        ) . input(
                            'basic',
                            'insert',
                            [
                                'div_attributes' => 'style:(display: none;);',
                                'attributes' => "min:(0.01); max:({$remaining_amount});",
                                'size' => 'col-12',
                                'type' => 'text',
                                'class' => 'mask-money',
                                'step' => '0.01',
                                'label' => 'Amount to refund',
                                'name' => "to_refund_amount",
                                'Alert' => "Must be between 0.01 and {$currency($remaining_amount)}",
                            ]
                        ) .input(
                            'submit_button',
                            'insert',
                            [
                                'size' => 'col-12',
                                'class' => 'btn btn-st btn-block',
                                'Value' => 'Confirm cancellation / refund'
                            ]
                        )

                        ."
                    </form>
                </div>
            </div>
            </div>
            </div>";
        }
        else
        {
            $res.= "
            <div class='info col-sm-12'>
                <span class='badge text-bg-danger'>Not refundable</span>
            </div>";
        }


        $res.= "
        <div class='info col-sm-12'>
            <hr>
        </div>";

    $res .= "</div>";

    return $res;
}


function order_headers_data(array $order = [])
{
    $res = "
    <div class='subject-data-list'>";

        $res.= "<h3>Order header</h3>";

        $res.= "
        <p>IP Address: <bdi>{$order['ip_address']}</bdi></p>";

        $res.= "
        <p>User agent: <bdi>{$order['user_agent']}</bdi></p>";

        $res.= "
        <p>Document type: <bdi>{$order['origin']}</bdi></p>";

        $res.= "
        <p>Origin: <bdi>{$order['origin']}</bdi></p>";

        $res.= "
        <p>Referer: <bdi>{$order['referrer']}</bdi></p>";


    $res .= "</div>";

    return $res;
}


/**
 * Renders the request "headers" frozen on tb_orders itself (ip_address,
 * user_agent, device_type, origin, referrer -- see prepare_order_create(),
 * src/helpers.php) as a simple key/value table. Used inside
 * order-manager.php's "Headers & UTM" offcanvas -- a table rather than
 * order_headers_data()'s plain list, to sit next to
 * render_order_utm_table_html() below as the offcanvas's two tables.
 *
 * @param array $order Row from tb_orders (get_order()'s 'order' key).
 * @return string
 */
function render_order_headers_table_html(array $order = []): string
{
    $rows = [
        'IP Address'  => $order['ip_address']  ?? '',
        'User agent'  => $order['user_agent']  ?? '',
        'Device type' => $order['device_type'] ?? '',
        'Origin'      => $order['origin']      ?? '',
        'Referrer'    => $order['referrer']    ?? '',
    ];

    $res = "<table class='table table-sm order-headers-table'><tbody>";

    foreach ($rows as $label => $value)
    {
        $value_html = ($value !== '' && $value !== null)
            ? e((string)$value)
            : "<span class='text-muted'>&mdash;</span>";

        $res.= "<tr><th scope='row'>{$label}</th><td><bdi>{$value_html}</bdi></td></tr>";
    }

    $res.= "</tbody></table>";

    return $res;
}

/**
 * Renders the UTM parameters captured for this order (see
 * capture_utm_params(), core, and save_order_utm_data(), src/helpers.php)
 * as a simple key/value table -- only the tags actually captured get a row;
 * an order with no tb_order_utm_data row at all (direct/organic traffic)
 * shows an empty state instead of a table full of blanks.
 *
 * @param array|null $utm_data Row from tb_order_utm_data (get_order()'s 'utm_data' key), or null/[] when none.
 * @return string
 */
function render_order_utm_table_html(?array $utm_data): string
{
    $labels = [
        'utm_source'   => 'Source',
        'utm_medium'   => 'Medium',
        'utm_campaign' => 'Campaign',
        'utm_content'  => 'Content',
        'utm_term'     => 'Term',
        'utm_resource' => 'Resource',
        'invite_code'  => 'Invite code',
    ];

    $rows = '';
    if (!empty($utm_data))
    {
        foreach ($labels as $key => $label)
        {
            if (empty($utm_data[$key])) continue;
            $rows.= "<tr><th scope='row'>{$label}</th><td><bdi>". e((string)$utm_data[$key]) ."</bdi></td></tr>";
        }
    }

    if ($rows === '') {
        return "<p class='text-muted mb-0'>This order has no UTM.</p>";
    }

    return "<table class='table table-sm order-utm-table'><tbody>{$rows}</tbody></table>";
}


/**
 * "{img_bandeira} {nome_bandeira} ** {last4} <br> expiração mm/aaaa" block,
 * shared between the "Meus cartões" table/cards (custom-pages/payment-methods.php)
 * and the "meio de pagamento" column of the payment-history listing
 * (custom-listings/payment-history.php).
 *
 * Accepts either a full tb_user_payment_methods row (with the
 * list_user_saved_payment_methods()-added 'expiration' key) or a raw row
 * where only brand_name/last4/exp_month/exp_year are present.
 *
 * @param array $card
 * @return string
 */
function render_card_brand_html(array $card): string
{
    $brand_name = (string)($card['brand_name'] ?? '');

    $default_key   = (string) get_system_info('default_payment_method');
    $default_label = $GLOBALS['payment_gateways'][$default_key]['label'] ?? '';

    if (empty($card)) {
        $brand_name = $default_label;
    }

    $name = $brand_name;
    $expiration = '';
    if (!empty($card['last4']))
    {
        $name = e(ucfirst($brand_name)) . " ** {$card['last4']}";
        $expiration = $card['expiration']
            ?? (str_pad((string)($card['exp_month'] ?? ''), 2, '0', STR_PAD_LEFT) . '/' . ($card['exp_year'] ?? ''));
        $expiration = "<small>{$expiration}</small>";
    }

    $icon_url  = card_icon_url($brand_name);
    $icon_html = !empty($icon_url)
        ? "<img loading='lazy' src='{$icon_url}' alt='". e($brand_name) ."' class='card-brand-icon'>"
        : '';

    return "
    <div class='card-brand-cell'>
        {$icon_html}
        <span class='card-brand-text'>
            <span>{$name}</span>
            {$expiration}
        </span>
    </div>";
}


/**
 * Realistic credit-card visual (gradient plastic, chip, masked number,
 * holder name, expiration, brand logo) -- one slide of the Bootstrap 5.3
 * carousel used both by the mobile view of custom-pages/payment-methods.php
 * and by the carousel-only A/B variant, custom-pages/payment-methods-carousel.php.
 *
 * All the actual "looks like a card" styling (gradient, aspect-ratio, chip
 * decoration, typography) lives in assets/styles/payment-methods.css under
 * .payment-card-visual -- this function only emits structure + data
 * attributes, so both pages/JS files can read data-payment-method-id off
 * the slide without re-parsing anything.
 *
 * @param array $card A row from list_user_saved_payment_methods().
 * @param int   $index Position in the carousel (0-based) -- alternates a
 *                      handful of gradient look variants via a CSS class,
 *                      purely cosmetic.
 * @return string
 */
function render_credit_card_visual_html(array $card, int $index = 0): string
{
    $brand_name  = (string)($card['brand_name'] ?? '');
    $last4       = e((string)($card['last4'] ?? '----'));
    $first6      = preg_replace('/\D+/', '', (string)($card['first6'] ?? ''));
    $expiration  = (string)($card['expiration'] ?? '');
    $holder      = e((string)($card['holder_name'] ?? ''));
    $issuer_name = e((string)($card['issuer_name'] ?? ''));
    $is_default  = !empty($card['is_default']);
    $is_active   = !empty($card['is_active']);
    $variant     = $index % 4;

    // Realistic 4-4-4-4 grouping using the digits we actually know (first6 +
    // last4) and masking the rest -- e.g. "1234 56•• •••• 7890". Falls back
    // to a fully masked number when first6 isn't available.
    $number_html = strlen($first6) === 6
        ? substr($first6, 0, 4) . ' ' . substr($first6, 4, 2) . '•• •••• ' . $last4
        : "•••• •••• •••• {$last4}";

    $icon_url  = card_icon_url($brand_name);
    $logo_html = !empty($icon_url)
        ? "<img loading='lazy' src='{$icon_url}' alt='". e($brand_name) ."' class='payment-card-visual-logo'>"
        : "<span class='payment-card-visual-logo-text'>" . e(ucfirst($brand_name)) . "</span>";

    // Always rendered (never an empty string) -- assets/scripts/payment-methods.js
    // updates this same element's text/class in place when the switch is
    // toggled, so it needs to exist in the DOM for every card, not just the
    // default/inactive ones.
    if ($is_default) {
        $status_html = "<span class='badge text-bg-light payment-card-visual-badge'>Padrão</span>";
    } elseif ($is_active) {
        $status_html = "<span class='badge text-bg-success payment-card-visual-badge'>Ativo</span>";
    } else {
        $status_html = "<span class='badge text-bg-danger payment-card-visual-badge'>Inativo</span>";
    }

    $issuer_html = $issuer_name !== ''
        ? "<span class='payment-card-visual-issuer'>{$issuer_name}</span>"
        : '';

    return "
    <div class='payment-card-visual payment-card-visual-variant-{$variant}" . (!$is_active ? ' payment-card-visual-inactive' : '') . "'>
        <div class='payment-card-visual-top'>
            {$issuer_html}
            {$status_html}
        </div>
        <div class='payment-card-visual-number'><span class='payment-card-visual-chip' aria-hidden='true'></span> {$number_html}</div>
        <div class='payment-card-visual-bottom'>
            <div class='payment-card-visual-holder'>
                <small>Titular</small>
                <span>" . ($holder !== '' ? $holder : '&nbsp;') . "</span>
            </div>
            <div class='payment-card-visual-expiration'>
                <small>Validade</small>
                <span>{$expiration}</span>
            </div>
            {$logo_html}
        </div>
    </div>";
}


/**
 * "Transações" section body for the carousel A/B page -- shared by the
 * server-rendered initial state (first card in the carousel) and the
 * `payment-method-details` REST route (fetched again every time the
 * carousel slides to a different card), so the markup can never drift
 * between the two.
 *
 * list_payment_method_transactions() already caps this at the last 5 --
 * the footer below always says so and links to the full
 * /historico-pagamentos/ listing for everything else.
 *
 * @param array $transactions Rows from list_payment_method_transactions().
 * @return string
 */
function render_payment_method_transactions_html(array $transactions): string
{
    if (empty($transactions))
    {
        return "
        <div class='payment-method-section-empty'>
            " . icon('fas fa-receipt') . "
            <p>Nenhuma transação encontrada para este cartão.</p>
        </div>";
    }


    $res = "";
    foreach ($transactions as $t)
    {
        $currency_code = strtoupper((string)($t['currency'] ?? '')) ?: DEFAULT_CURRENCY;
        $descriptor    = e((string)($t['statement_descriptor'] ?? ''));
        $item_name     = e((string)($t['first_item_name'] ?? ''));
        $extra         = (int)($t['total_items'] ?? 0) - 1;
        $extra_html    = $extra > 0 ? " <span class='badge text-bg-secondary'>+{$extra}</span>" : '';

        $installments  = (int)($t['total_items'] ?? 1);
        $installments  = ($installments > 1)
            ? "<small> em {$installments}x</small>"
            : "";

        $res .= "
        <div class='payment-method-transaction-item'>
        <div>
            <!-- <strong>#{$t['order_id']}</strong> -  -->
            <h4>{$item_name}{$extra_html}</h4>
            <small>" . date('d/m/Y', strtotime((string)$t['created_at'])) . "</small>
        </div>
        <div class='amount'>
        <div>
            <span class='total_amount'>". $currency_code((float)$t['amount']) ."
            <br> {$installments}
            " . general_stats($t['order_status_id'], 'order_status', 'button') . "
        </div>
        </div>
        </div>";
    }

    return $res;
}


/**
 * "Assinaturas" section body for the carousel A/B page -- mirrors
 * render_payment_method_transactions_html() above; see that docblock.
 * list_payment_method_subscriptions() also caps this at the last 5; there's
 * no dedicated "minhas assinaturas" listing today, so the footer here only
 * informs the cap, with no link.
 *
 * @param array $subscriptions Rows from list_payment_method_subscriptions().
 * @return string
 */
function render_payment_method_subscriptions_html(array $subscriptions): string
{
    global $seg;

    // subscription_status_badge() lives in src/subscriptions/helpers.php,
    // which is only require_once'd from src/subscriptions/index.php when
    // the subscriptions feature is active (index.php) -- not auto-loaded
    // at plugin boot like this file is, so it's pulled in on demand here,
    // same idiom save_payment_method() uses for helpers.php.
    require_once __DIR__ .'/subscriptions/helpers.php';

    if (empty($subscriptions))
    {
        return "
        <div class='payment-method-section-empty'>
            " . icon('fas fa-sync-alt') . "
            <p>Nenhuma assinatura atrelada a este cartão.</p>
        </div>";
    }

    $res = "";
    foreach ($subscriptions as $s)
    {
        $plan_name = e((string)($s['plan_name'] ?? ''));
        $cadence   = "{$s['interval_count']}x " . e((string)($s['interval_unit'] ?? ''));
        $next      = !empty($s['next_billing_at']) ? format_date_long_ptbr((string)$s['next_billing_at']) : '--';

        $next_billing = !($s['status'] == 'canceled' OR $s['status'] == 'expired' OR $s['status'] == 'paused')
            ?  "<br><small class='text-muted'>Próxima cobrança: <br>{$next}</small>"
            : '';

        $res .= "
        <div class='payment-method-transaction-item'>
        <div>
            <strong>{$plan_name}</strong>
            {$next_billing}
        </div>
        <div class='text-end'>
            ". general_stats($s['status'], 'subscription_status', 'button') ."
        </div>
        </div>";
    }

    return $res;
}
