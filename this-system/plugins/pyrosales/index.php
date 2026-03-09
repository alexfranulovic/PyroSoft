<?php
if (!isset($seg)) exit;

define('DEFAULT_CURRENCY', $config['default_currency']);


global $tables;
$tables['tb_orders'] = 'Pedidos';
$tables['tb_order_payments'] = 'Pagamentos';
$tables['tb_order_items'] = 'Itens do pedido';
$tables['tb_order_fees'] = 'Taxas do pedido';
$tables['tb_order_coupons'] = 'Cupons do pedido';


if (!isset($payment_gateways)) {
    global $payment_gateways;
    $payment_gateways = [];
}

require __DIR__ .'/src/status.php';
require __DIR__ .'/src/helpers.php';
require __DIR__ .'/src/view.php';
require __DIR__ .'/src/ui.php';


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

    $res = [];

    $data = prepare_order_create($payload, $payment_gateways);
    mysqli_begin_transaction($conn);

    // print_r($data);
    // die;

    try
    {
        if (empty($payload['user_id']) && !empty($payload['create-user']))
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
            $data['order']['user_id'] = $userId;
        }

        // die;

        /**
         *
         * 1) Insert order
         *
         */
        $orderId = insert('tb_orders', $data['order']);
        $orderId = inserted_id();
        if (!$orderId) throw new Exception("Failed to create order.");
        $data['order']['id'] = $orderId;

        /**
         *
         * 2) Insert items
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

        /**
         *
         * 3) Insert coupons (if any)
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

        /**
         *
         * 4) Insert fees (if any)
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

        /**
         *
         * 5) Insert payemnt (if any)
         *
         */
        if (!empty($data['payment_template']['provider']))
        {
            $provider = $data['payment_template']['provider'];
            $method   = $data['payment_template']['method'];

            // print_r($data['payment_template']);
            // die;

            $gateway_response = [];


            /**
             * 5.1) Process payment according with provider
             */
            if (!empty($payload['process-payment']) && !empty($orderId))
            {
                $process_payment_function = "{$provider}_{$method}_process_payment";
                require_once plugin_path("{$provider}/src/process-payment.php");

                $gateway_response = (array)$process_payment_function($data);

                // If your gateway function returns ["code"=>..,"msg"=>..], unwrap msg
                if (isset($gateway_response['msg']) && is_array($gateway_response['msg'])) {
                    $gateway_response = $gateway_response['msg'];
                }
            }


            /**
             * 5.2) Create payment (build row from base + gateway)
             */
            $basePayment = [
                'order_id'  => $orderId,
                'method'    => $method,
                'provider'  => $provider,
                'currency'  => $data['payment_template']['currency'] ?? DEFAULT_CURRENCY,
                'amount'    => $data['payment_template']['amount'] ?? 0,
            ];

            // Merge: gateway wins when it provides fields
            $merged = array_merge($basePayment, array_filter($gateway_response, function ($v) {
                return $v !== null && $v !== '';
            }));

            $paymentRow = build_payment_line($merged);

            $paymentId = insert('tb_order_payments', $paymentRow);
            $paymentId = inserted_id();
            if (!$paymentId) throw new Exception("Failed to create payment.");

            $paymentRow['id'] = $paymentId;
        }


        mysqli_commit($conn);

        // Return full order info (snapshot)
        return [
            'code' => 'success',
            'order' => $data['order'],
            'items' => $items,
            'coupon_lines' => $coupons,
            'fee_lines' => $fees,
            'payment' => $paymentRow ?? [],
        ];

    } catch (Exception $e) {

        mysqli_rollback($conn);
        throw $e;
    }
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
    if ($orderId <= 0) throw new Exception("Invalid order_id.");

    // Whitelist: only fields you want to allow editing
    $allowed = [
        'status_id',
        'notes',
        'requires_address',
        'address',
        'vendor_id',
        'commission_amount',
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

    $set['updated_at'] = date('Y-m-d H:i:s');

    if (!$set) throw new Exception("No editable fields provided.");

    $res = update('tb_orders', $set, "id = '" . (int)$orderId . "'", true);

    return [
        'code' => 'success',
        'updated' => $res,
        'order_id' => $orderId,
    ];
}
