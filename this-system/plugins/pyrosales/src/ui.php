<?php
if (!isset($seg)) die;

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
            $res .="
            <p>
                <span>Discount(s):</span>
                <bdi>-{$currency($order['discount_amount'])}</bdi>
            </p>";
        }

        if ($order['fee_amount'] > 0)
        {
            $res .="
            <p>
                <span>Fee(s):</span>
                <bdi>{$currency($order['fee_amount'])}</bdi>
            </p>";
        }

        if ($order['shipping_amount'] > 0)
        {
            $res .="
            <p>
                <span>Shipping:</span>
                <bdi>{$currency($order['shipping_amount'])}</bdi>
            </p>";
        }

        if ($order['tax_amount'] > 0)
        {
            $res .="
            <p>
                <span>Tax(s):</span>
                <bdi>{$currency($order['tax_amount'])}</bdi>
            </p>";
        }

        $res .="
        <hr>
        <p>
            <span>Total amount:</span>
            <bdi>{$currency($order['total_amount'])}</bdi>
        </p>

    </div>";

    return $res;
}


function order_customer_data(array $order = [])
{
    $document_type = $order['customer_document_type'] ?? '';
    $res = "
    <div class='subject-data-list'>";

        $res .="<h3>Customer data</h3>";

        $res .="
        <p>". icon('fas fa-user') ." <bdi>{$order['customer_first_name']} {$order['customer_last_name']}</bdi></p>";

        $res .="
        <p>". icon('fas fa-envelope') ." <a href='mailto:{$order['customer_email']}'><bdi>{$order['customer_email']}</bdi></a></p>";

        $res .="
        <p>". icon('fas fa-phone') ." <a href='tel:{$order['customer_phone']}'><bdi>{$order['customer_phone']}</bdi></a></p>";

        $res .="
        <p>". icon('fab fa-whatsapp') ." <a target='_blank' href='http://wa.me/{$order['customer_phone']}'><bdi>{$order['customer_phone']}</bdi></a></p>";

        $res .="
        <p>". icon('fas fa-id-badge') ." {$document_type}: <bdi>". $document_type($order['customer_document_number'])."</bdi></p>";

    $res .= "</div>";

    return $res;
}


function payment_details(array $payment = [])
{
    $currency = $payment['currency'] ?? DEFAULT_CURRENCY;

    $res = "
    <div class='payment-details form-row'>";

        // $res .="
        // <div class='info col-sm-12'>
        //     <h3>Customer data</h3>
        // </div>";

        $res .="
        <div class='info col-sm-6'>
            <h4>Status:</h4>
            <p>". general_stats($payment['status_id'], 'payment_status') ."</p>
        </div>";

        $res .="
        <div class='info col-sm-6'>
            <h4>Currency:</h4>
            <p>{$currency}</p>
        </div>";

        $res .="
        <div class='info col-sm-6'>
            <h4>Amount:</h4>
            <p>{$currency((float) $payment['amount'])}</p>
        </div>";

        $res .="
        <div class='info col-sm-6'>
            <h4>Method:</h4>
            <p>{$payment['method']}</p>
        </div>";

        $res .="
        <div class='info col-sm-6'>
            <h4>Provider:</h4>
            <p>{$payment['provider']}</p>
        </div>";

        $res .="
        <div class='info col-sm-12'>
            <hr>
        </div>";

        if (!empty($payment['payment_link']))
        {
            $res .="
            <div class='info col-sm-6'>
                <h4>Payment link:</h4>
                <p><a href='{$payment['payment_link']}'>{$payment['payment_link']}</p>
            </div>";
        }

        if (!empty($payment['gateway_fee']))
        {
            $res .="
            <div class='info col-sm-6'>
                <h4>Gateway fee:</h4>
                <p>{$currency((float) $payment['gateway_fee'])}</p>
            </div>";
        }

        if (!empty($payment['net_amount']))
        {
            $res .="
            <div class='info col-sm-6'>
                <h4>Net amount:</h4>
                <p>{$currency((float) $payment['net_amount'])}</p>
            </div>";
        }

        if (!empty($payment['installments']) && !empty($payment['installment_amount']))
        {
            $res .="
            <div class='info col-sm-6'>
                <h4>Installments:</h4>
                <p>{$payment['installments']}x {$currency((float) $payment['installment_amount'])}</p>
            </div>";
        }


        if (!empty($payment['raw_response_json']))
        {
            $res .="
            <div class='info col-sm-12'>
                <h4>Provider response:</h4>
                <pre><code>". json_encode($payment['raw_response_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ."</code></pre>
            </div>";
        }

        $res .="
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

        $res .="<h3>Order header</h3>";

        $res .="
        <p>IP Address: <bdi>{$order['ip_address']}</bdi></p>";

        $res .="
        <p>User agent: <bdi>{$order['user_agent']}</bdi></p>";

        $res .="
        <p>Document type: <bdi>{$order['origin']}</bdi></p>";

        $res .="
        <p>Referer: <bdi>{$order['referrer']}</bdi></p>";

        $res .="
        <p>Origin: <bdi>{$order['origin']}</bdi></p>";


    $res .= "</div>";

    return $res;
}
