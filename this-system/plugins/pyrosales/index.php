<?php
if (!isset($seg)) exit;

define('DEFAULT_CURRENCY', $config['default_currency']);


$GLOBALS['tables']+= [
    'tb_orders' => 'Pedidos',
    'tb_order_notes' => 'Notas de pedidos',
    'tb_order_payments' => 'Pagamentos',
    'tb_order_items' => 'Itens do pedido',
    'tb_order_fees' => 'Taxas do pedido',
    'tb_order_coupons' => 'Cupons do pedido',
    'tb_order_utm_data' => 'UTMs do pedido',
];


$GLOBALS['alerts']+=
[
    'ER_TO_CANCEL_OR_REFUND_PAYMENT' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => '**Não foi possível** cancelar ou reembolsar o pagamento: -br'
    ],
    'ER_TO_ADD_ORDER_NOTE' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => '**Não foi possível** Adicionar uma nota ao pedido, tente novamente mais tarde.'
    ],
    'ER_TO_SAVE_PAYMENT_METHOD' => [
        'color' => 'danger', 'close_button' => true,
        'title' => 'Erro!',
        'body' => '**Não foi possível salvar** o cartão: -br'
    ],
];


require_once __DIR__ .'/src/status.php';
require_once __DIR__ .'/src/view.php';
require_once __DIR__ .'/src/ui.php';
require_once __DIR__ .'/src/payment_methods.php';
require_once __DIR__ .'/src/notes.php';
require_once __DIR__ .'/src/cancel-refund.php';
require_once __DIR__ .'/src/one-off-cart.php';

function load_pyrosales_subscriptions()
{
    global $seg;
    require_once __DIR__ .'/src/subscriptions/index.php';
}

// Gambiarra, precisa criar um sistema de ordenação de carregamento de plugins para o plugin de planos vir antes do pyrosales
load_pyrosales_subscriptions();

function load_pyrosales_notifications()
{
    global $seg;
    require_once __DIR__ .'/src/notifications.php';
}

/**
 * Creates a full order (orders + items + coupons + fees + payment)
 * and returns the created order data (including lines).
 *
 * @param array $payload
 * @param array $payment_gateway
 * @return array
 * @throws Exception
 */
function create_order(array $payload): array
{
    global $payment_gateways;
    global $conn;
    global $seg;
    global $info;

    $res                    = [];
    $error                  = false;
    $gateway_errors         = [];
    $order_status           = 'failed';
    $order_status_id        = 5;
    $all_info               = $payload['all_info'] ?? false;
    $redirect_payment_page  = false;
    $capture                = true;
    $payment_status         = 3;
    $order_purpose          = 'charge';

    require_once __DIR__ .'/src/helpers.php';

    $data          = prepare_order_create($payload);
    $order_purpose = $data['order']['order_purpose'] ?? $order_purpose;

    /**
     * BYPASS PAYMENT (plans-subscriptions integration)
     *
     * When this order bills a subscription whose tb_plan_user_subscriptions.
     * bypass_payment flag is enabled, skip the gateway entirely -- used for
     * staff-granted/comped subscription cycles that should never actually
     * hit the payment gateway.
     */
    $bypass_payment = false;
    if (
        ($data['order']['order_type']) === 'plan' && !empty($data['order']['subscription_id'])
        && function_exists('get_subscription')
    ){
        $bypass_subscription = get_subscription((int) $data['order']['subscription_id']);
        $bypass_payment      = !empty($bypass_subscription['bypass_payment']);
    }

    if ($bypass_payment) {
        $payload['process-payment'] = false;
        $order_status_id = 3;
        $payment_status  = 2;
        $data['order']['status_id'] = $order_status_id;
    }

    /**
     * Checkout Settings
     */
    $login_user_after_checkout  = get_system_info('pyrosales_login_user_after_checkout');
    $create_user_after_checkout = get_system_info('pyrosales_create_user_after_checkout');


    /**
     * Create user if:
     * - order type = plan | user needs an account to use the plan.
     * - payload asked for | can be a customized order by API, for example.
     * - it's setted in the panel | it can be business logic.
     * OBS.:
     *      - The user_id can be passed forced in paylod, else if try
     *      the logged user, then defaulted as null.
     */
    $create_user = (($data['order']['order_type'] == 'plan') || (!empty(($payload['create-user']??false) || $create_user_after_checkout)));
    $login_after = (($data['order']['order_type'] == 'plan') || $login_user_after_checkout);
    $userId      = $payload['user_id'] ?? null;


    $payload['calc'] = $data['calc'] ?? [];

    // print_r($data);
    // print_r($payload);
    // die;

    if (empty($data['errors']))
    {
        $data['errors'] = [];

        try
        {
            /**
             *
             * Create User
             *
             */
            if (empty($payload['user_id']) && $create_user)
            {
                $user_data = [
                    'first_name' => $data['order']['customer_first_name'],
                    'last_name' => $data['order']['customer_last_name'],
                    'email' => $data['order']['customer_email'],
                    'phone' => $data['order']['customer_phone'],
                    'document_type' => $data['order']['customer_document_type'],
                    'document_number' => $data['order']['customer_document_number'],
                ];

                $userId = insert('tb_users', $user_data);
                $userId = inserted_id();
                $payload['user_id'] = $userId;
                $data['order']['user_id'] = $userId;

                // Add user roles.
                edit_user_role_assignments($userId, []);
            }

            // Login after create user
            if ($userId && $login_after && !is_user_logged_in())
            {
                user_login([
                    'user' => $userId,
                    'force' => true,
                ]);
            }

            /**
             *
             * 1.5) Resolve vendor from invite_code -- whatever
             * capture_utm_params() (core) captured for this visitor's
             * session/cookie. Only kicks in when the caller didn't already
             * pass an explicit vendor_id (e.g. a manual order created from
             * order-manager.php) -- prepare_order_create() already put that
             * value on $data['order']['vendor_id'].
             *
             * The invite_code owner can't be their own vendor: buying
             * through your own code just finalizes the order normally,
             * with vendor_id left NULL, instead of failing.
             *
             */
            if (empty($payload['vendor_id']))
            {
                $invite_code = trim((string) ($_SESSION['invite_code'] ?? $_COOKIE['invite_code'] ?? ''));

                if ($invite_code !== '')
                {
                    $vendor_id = get_col("SELECT id FROM tb_users WHERE invite_code = '" . addslashes($invite_code) . "'");

                    if (!empty($vendor_id) && (int) $vendor_id !== (int) $userId) {
                        $data['order']['vendor_id'] = (int) $vendor_id;
                        $data['order']['commission_activation_function'] = get_system_info('pyrosales_commission_activation_function') ?? null;
                    }
                }
            }

            // Init transaction
            mysqli_begin_transaction($conn);


            /**
             *
             * 0) (Only order type plan) Force
             *
             */
            if ($data['order']['order_type'] == 'plan')
            {
                $plan       = $data['items_full'][0] ?? [];
                $plan_id    = $plan['id'] ?? null;
                $existing_subscription = get_user_active_plan_subscription($userId, $plan_id);

                if (($plan['trial_days'] > 0 && $order_purpose != 'subscription_renewal')
                    && empty($existing_subscription['id']))
                {
                    $capture = false;
                    $order_purpose = 'trial_validation';
                }
            }
            $data['capture'] = $capture;


            /**
             *
             * 1) Treat payment data.
             *
             */
            $payment_treated    = prepare_payment_data($payload);
            $data               = array_merge($data, $payment_treated);

            $payment_data           = $payment_treated['payment_data'] ?? [];
            $payment_template       = $payment_treated['payment_template'] ?? [];
            $payment_data           = $data['payment_data'] ?? [];
            $statement_descriptor   = $payment_data['statement_descriptor'] ?? $info['short_name'];
            $user_payment_method_id = $payment_data['user_payment_method_id'] ?? null;


            /**
             *
             * 2) Insert order
             *
             */
            $orderId = insert('tb_orders', $data['order']);
            $orderId = inserted_id();
            if (!$orderId) throw new Exception("Failed to create order.");
            $data['order']['id'] = $orderId;


            /**
             * BYPASS PAYMENT (continued from above): force the order
             * straight to paid, same numeric codes a real successful charge
             * would land on (order_status_id 3 = 'paid', payment_status 2 =
             * 'paid' -- see src/status.php) so the plan activation check
             * further down (`$order_status_id == 3`) fires exactly as it
             * would for a real payment.
             */
            if ($bypass_payment) {
                $order_status_id = 3;
                $payment_status  = 2;
                $data['order']['status_id'] = $order_status_id;

                query_it("
                    UPDATE tb_orders
                    SET status_id = '{$order_status_id}'
                    WHERE id = '{$orderId}'
                    LIMIT 1
                ");
            }

            /**
             *
             * 2.1) Save UTM snapshot -- whatever capture_utm_params() (core,
             * ran earlier this request and stashed in $_SESSION/$_COOKIE)
             * captured for this visitor, frozen against the order that just
             * got created.
             *
             */
            save_order_utm_data($orderId);

            /**
             *
             * 3) Insert items
             *
             */
            $items = [];
            foreach ($data['items_lines'] as $key => $row)
            {
                $row['order_id'] = $orderId;

                insert('tb_order_items', $row);
                $id = inserted_id();
                if (!$id) throw new Exception("Failed to create order item.");

                $row['id'] = $id;
                $items[] = $row;

                $data['items_lines'][$key]['id'] = $id;
            }
            $data['items_lines'] = $items;

            /**
             *
             * 4) Insert coupons (if any)
             *
             */
            $coupons = [];
            foreach ($data['coupon_lines'] as $key => $row)
            {
                $row['order_id'] = $orderId;

                insert('tb_order_coupons', $row);
                $id = inserted_id();
                if (!$id) throw new Exception("Failed to create order coupon.");

                $row['id'] = $id;
                $coupons[] = $row;

                $data['coupon_lines'][$key]['id'] = $id;
            }
            $data['coupon_lines'] = $coupons;

            /**
             *
             * 5) Insert fees (if any)
             *
             */
            $fees = [];
            foreach ($data['fee_lines'] as $key => $row)
            {
                $row['order_id'] = $orderId;

                insert('tb_order_fees', $row);
                $id = inserted_id();
                if (!$id) throw new Exception("Failed to create order fee.");

                $row['id'] = $id;
                $fees[] = $row;

                $data['fee_lines'][$key]['id'] = $id;
            }
            $data['fee_lines'] = $fees;

            /**
             *
             * 6) Insert payemnt (if any)
             *
             */
            if (!empty($payment_template['provider']))
            {
                $provider = $payment_template['provider'];
                $method   = $payment_template['method'];


                /**
                 * 6.1) Process payment according with provider
                 */
                $gateway_response = [];
                if (!empty($payload['process-payment']) && !empty($orderId))
                {
                    require_once plugin_path("{$provider}/src/process-payment.php");

                    $process_payment_function = "{$provider}_{$method}_process_payment";
                    $gateway_response = (array)$process_payment_function($data);

                    /**
                     * Set payment as 'failed' in case of error, and log the
                     * decline here -- this is the single point every
                     * provider/method converges on (they all normalize to
                     * ['code' => 'success'|'error', ...]), so this replaces
                     * the ad-hoc app_log() calls each gateway function used
                     * to duplicate (with diverging, sometimes wrong, status
                     * checks) with one standard place.
                     */
                    if ($gateway_response['code'] == 'error')
                    {
                        $error = true;
                        $order_status = 'failed';
                        $order_status_id = 5;

                        app_log('error', 'Pagamento recusado pelo gateway', [
                            'file_name' => 'payments.log',
                            'origin'    => 'payment',
                            'gateway'   => $provider,
                            'order'     => [
                                'order_id'      => $orderId,
                                'user_id'       => $data['order']['user_id'] ?? null,
                                'method'        => $method,
                                'amount'        => $payment_template['amount'] ?? null,
                                'order_purpose' => $order_purpose,
                            ],
                            'gateway_response' => $gateway_response,
                        ]);
                    }

                    // Return error messages to client.
                    if (!empty($gateway_response['user_payment_method_id'])) {
                        $user_payment_method_id = $gateway_response['user_payment_method_id'];
                    }

                    // Return error messages to client.
                    if (!empty($gateway_response['error_messages'])) {
                        $gateway_errors = $gateway_response['error_messages'];
                    }

                    // Gateway requires payment link
                    if (!empty($gateway_response['redirect_payment_page'])) {
                        $redirect_payment_page = true;
                    }

                    // If your gateway function returns ["code"=>..,"msg"=>..], unwrap msg
                    if (isset($gateway_response['msg']) && is_array($gateway_response['msg'])) {
                        $gateway_response = $gateway_response['msg'];
                    }

                    /**
                     * If the gateway returned a status_id, synchronize it with the order.
                     */
                    if (!$error && !empty($gateway_response['status_id'])) {
                        $payment_status = (int)$gateway_response['status_id'];
                    }

                    $order_status_id = payment_to_order_status([
                        'payment_status' => $payment_status,
                        'purpose' => $order_purpose,
                    ]);

                    $data['order']['status_id'] = $order_status_id;

                    query_it("
                        UPDATE tb_orders
                        SET
                            status_id = '{$order_status_id}',
                            order_purpose = '{$order_purpose}'
                        WHERE id = '{$orderId}'
                        LIMIT 1
                    ");
                }


                /**
                 * 6.2) Create payment (build row from base + gateway)
                 */
                $currency       = $payment_template['currency'] ?? DEFAULT_CURRENCY;
                $amount         = $currency($payment_template['amount'] ?? 0);

                $basePayment = [
                    'order_id'  => $orderId,
                    'method'    => $method,
                    'provider'  => $provider,
                    'currency'  => $currency,
                    'amount'    => $amount,
                    'payment_hash' => $payment_template['payment_hash'] ?? '',
                ];

                // Merge: gateway wins when it provides fields
                $merged = array_merge($basePayment, array_filter($gateway_response, function ($v) {
                    return $v !== null && $v !== '';
                }));

                if (!empty($user_payment_method_id)) {
                    $merged['user_payment_method_id'] = $user_payment_method_id;
                }

                if (!empty($statement_descriptor)) {
                    $merged['statement_descriptor'] = $statement_descriptor;
                }

                $paymentRow = build_payment_line($merged);

                $paymentId = insert('tb_order_payments', $paymentRow);
                $paymentId = inserted_id();
                if (!$paymentId) throw new Exception("Failed to create payment.");

                $paymentRow['id'] = $paymentId;
                $data['payment'] = $paymentRow;
            }

            /**
             * 6.3) Build de order note.
             */
            $msg_order_status = general_stats($order_status_id, 'order_status', 'title');
            $msg_payment_status = general_stats($payment_status, 'payment_status', 'name');

            $note_content = "
            Order created by **{$data['order']['customer_first_name']}** with status **{$msg_order_status}**. -br
            - Payment status: **{$msg_payment_status}** -br
            - Payment amount: **{$amount}** -br
            ";

            /**
             *
             * 7) Add note with context
             *
             */
            /**
             * 7.1) Add note with context
             */
            add_order_note([
                'order_id'  => $orderId,
                'content'   => $note_content,
                'note_type' => 'order_created',
                'body_type' => 'alert'
            ]);

            /**
             * 7.2) Add note with context
             */
            add_order_note([
                'order_id'  => $orderId,
                'content'   => $paymentRow['raw_response_json'],
                'note_type' => 'order_created',
                'body_type' => 'accordion'
            ]);

            /**
             * 8.1) (Only order type plan) Activate a plan
             */
            if ($data['order']['order_type'] == 'plan' && $order_status_id == 3)
            {
                activate_order_plan_subscription([
                    'order_id'               => $orderId,
                    'user_id'                => $userId,
                    'plan'                   => $plan,
                    'order_purpose'          => $order_purpose,
                    'user_payment_method_id' => $user_payment_method_id,
                    'statement_descriptor'   => $statement_descriptor,
                ]);
            }

            /**
             * 8.2) (Only order type one_off, already paid) Use activation
             * function and drop the cart cookie -- its job is done, and a
             * page reload shouldn't try to sell the same item again.
             */
            if ($data['order']['order_type'] == 'one_off' && $order_status_id == 3)
            {
                foreach ($items as $order_item)
                {
                    if (empty($order_item['activation_function'])) continue;

                    function_process(
                        $order_item['activation_function'],
                        'activation_function',
                        $data
                    );
                }

                clear_one_off_cart_item();
            }

            /**
             * 9) (Vendor commission) Settle the seller's commission the
             * moment the order lands paid: run the frozen
             * commission_activation_function (tb_orders) and flip
             * commission_status_id 'pending' -> 'complete' when it
             * acknowledges the payout. No-op when there's no vendor_id or
             * no function -- the order just stays 'pending'. See
             * resolve_order_commission_status() (src/status.php).
             */
            if ($order_status_id == 3 && !empty($data['order']['vendor_id']))
            {
                resolve_order_commission_status(
                    array_merge($data['order'], [
                        'id'                             => $orderId,
                        'commission_activation_function' => $data['order']['commission_activation_function'] ?? null,
                        'commission_status_id'            => $data['order']['commission_status_id'] ?? 'pending',
                    ]),
                    $data
                );
            }

            // Commit the changes
            mysqli_commit($conn);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            throw $e;
        }
    }

    else {
        $error = true;
    }


    /**
     *
     * Build the response
     *
     */
    $res['code'] = !$error ? 'success' : 'error';
    if ($all_info)
    {
        $res+= [
            'order' => $data['order'],
            'items' => $items,
            'coupon_lines' => $coupons,
            'fee_lines' => $fees,
            'payment' => $paymentRow ?? [],
        ];
    }

        // var_dump($order_status_id);
    /**
     *
     * Redirect logic
     *
     */
    // Redirect to the payment page.
    if ($redirect_payment_page)
    {
        $checkout_page_id = get_system_info('checkout_page_id');
        $checkout_url     = get_url_page($checkout_page_id, 'full');

        $res['redirect'] = "{$checkout_url}/pay/{$orderId}";
    }

    // Redirect to the receipt page.
    elseif ($order_status_id == 2 || $order_status_id == 3)
    {
        $receipt_page_id = get_system_info('receipt_page_id');
        $receipt_url     = get_url_page($receipt_page_id, 'full');

        $res['redirect'] = "{$receipt_url}/{$orderId}";
    }


    /**
     *
     * Build the error and return to client.
     *
     */
    if ($error)
    {
        $errors = array_merge($gateway_errors, $data['errors']);

        $ul = '';
        $ul = '<ul>';
        foreach ($errors as $error_reason) {
            $ul.= "<li>{$error_reason}</li>";
        }
        $ul.= '</ul>';

        $body = "
        <p class='icon'>". icon('fas fa-circle-xmark') ."</p>
        <p class='title'>Não foi possível concluir o pagamento</p>
        {$ul}";

        $detail = block('modal', [
            'id' => 'payment-error-modal',
            'attributes' => 'data-modal:(true);',
            'size' => 'dialog-centered',
            'close_button' => true,
            'body' => $body,
        ]);

        $res['detail'] = [
            'type' => 'modal',
            'msg' => $detail
        ];
    }

    // Return full order info (snapshot)
    return $res;
}


/**
 * Updates an order (tb_orders) with a safe whitelist of fields.
 * NOTE: This updates only the order header. If you need to update items/coupons/fees,
 * do it with specific functions to avoid breaking financial history.
 *
 * @param int $orderId
 * @param array $data
 * @return array
 * @throws Exception
 */
function update_order(int $orderId, array $data): array
{
    require_once __DIR__ .'/src/helpers.php';

    if ($orderId <= 0) throw new Exception("Invalid order_id.");

    // Whitelist: only fields you want to allow editing
    $allowed = [
        'status_id',
        'notes',
        'requires_address',
        'address',
        'vendor_id',
        'commission_amount',
        'commission_activation_function',
        'commission_status_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_document_type',
        'customer_document_number',
    ];

    $set = [];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $data)) $set[$k] = $data[$k];
    }

    if (isset($set['requires_address'])) $set['requires_address'] = (int)!!$set['requires_address'];

    if (array_key_exists('address', $set)) {
        $set['address'] = is_array($set['address'])
            ? json_encode($set['address'], JSON_UNESCAPED_UNICODE)
            : ($set['address'] ?: null);
    }

    if (isset($set['commission_amount']) && $set['commission_amount'] !== null) {
        $set['commission_amount'] = number_format((float)$set['commission_amount'], 2, '.', '');
    }

    // ENUM guard: only the two documented states are accepted.
    if (isset($set['commission_status_id']) && !in_array($set['commission_status_id'], ['pending', 'complete'], true)) {
        unset($set['commission_status_id']);
    }

    if (array_key_exists('commission_activation_function', $set)) {
        $set['commission_activation_function'] = trim((string) $set['commission_activation_function']) ?: null;
    }

    $set['updated_at'] = date('Y-m-d H:i:s');

    if (!$set) throw new Exception("No editable fields provided.");

    $res = update('tb_orders', $set, "id = '" . (int)$orderId . "'", true);

    return [
        'code' => 'success',
        'updated' => $res,
        'order_id' => $orderId,
    ];
}

function checkout_load_gateways_head()
{
    global $config, $payment_gateways;

    $active_payment_methods = $config['active_payment_methods'] ?? [];

    $res = [];
    foreach (($payment_gateways ?? []) as $key => $gateway)
    {
        $key = explode('.', $key);
        $gateway_method = implode('_', $key);

        $gateway_method = "{$gateway_method}_head";
        if (function_exists($gateway_method)) {
            $gateway_method();
        }
    }
}

// process_plan_subscriptions_cron();
