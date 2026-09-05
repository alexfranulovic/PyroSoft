<?php
if (!isset($seg)) exit;

/**
 * Normalize PagBank transactional status to local status id.
 *
 * Adjust ids to match your database.
 *
 * Suggested mapping:
 * 1 = pending
 * 2 = approved
 * 3 = refused
 * 4 = canceled
 * 5 = authorized
 *
 * @param string $status PagBank charge status.
 *
 * @return int|null
 */
function pagbank_payment_status_id(array $params = [])
{
    $status = $params['status'] ?? null;
    if (empty($status)) {
        return 3;
    }


    $status           = strtoupper(trim($status));
    $legacy           = $params['legacy'] ?? false;
    $reverse          = $params['reverse'] ?? false;
    $for_notification = $params['for_notification'] ?? false;
    $order_data       = $params['order_data'] ?? [];

    /**
     * The reason is because the PagBank uses V4 to do orders, cancels & refunds.
     * And uses V2 for "notifications" (Webhooks), then we need to do this kludge with the gateways statuses.
     *
     * This is for V4 (LATEST).
     */
    if (!$legacy)
    {
        // Reverse
        if ($reverse)
        {
            $map = [
                1 => 'WAITING',
                9 => 'IN_ANALYSIS',
                2 => 'PAID',
                3 => 'DECLINED',
                3 => 'FAILED',
                4 => 'REFUNDED',
                6 => 'PARTIALLY_REFUNDED',
                5 => 'CANCELED',
                7 => 'AUTHORIZED',
                8 => 'CHARGEBACK',
                10 => 'DISPUTE',
            ];

            return $map[$status] ?? 'FAILED';  // Failed
        }

        $map = [
            // Pending states
            'WAITING'      => 1,
            'IN_ANALYSIS'  => 9,

            // Payment approved
            'PAID'         => 2,

            // Payment failed
            'DECLINED'     => 3,
            'FAILED'     => 3,

            // Refunds
            'REFUNDED'            => 4,
            'PARTIALLY_REFUNDED'  => 6,

            // Cancel
            'CANCELED'     => 5,

            // Authorization (capture later)
            'AUTHORIZED'   => 7,

            // Chargeback / dispute
            'CHARGEBACK'   => 8,
            'DISPUTE'      => 10,
        ];

        return $map[$status] ?? 3;  // Failed
    }

    /**
     * The reason is because the PagBank uses V4 to do orders, cancels & refunds.
     * And uses V2 for "notifications" (Webhooks), then we need to do this kludge with the gateways statuses.
     *
     * This is for V2 (OLDEST & Legacy).
     */
    if ($for_notification)
    {
        $map = [
            // pending
            '1' => 1,

            // fraud_review
            '2' => 9,

            // paid
            '3' => 2,
            '4' => 2,

            '6' => 4, // refunded
            '7' => 5, // canceled
            '8' => 8, // chargeback

            // In dispute
            '9' => 10,
            '5' => 10,
        ];

        $status = $map[$status] ?? 5; // Failed

        // PyroSales' logic of refund status.
        if ($order_data['payment_status_id'] == 4 || $order_data['payment_status_id'] == 6) {
            $status = $order_data['payment_status_id'];
        }

        return $status;
    }

    if ($reverse)
    {
        // Legacy reverse (PagBank)
        $map = [
            '1' => 1,
            '2' => 9,
            '3' => 2,
            '4' => 2,
            '6' => 4,
            '7' => 5,
            '8' => 8,
            '9' => 10,
            '5' => 10,
        ];

        return $map[$status] ?? 2;  // Failed
    }


    // Legacy (PagBank)
    $map = [
        // pending
        '1' => 1,

        // fraud_review
        '2' => 9,

        // paid
        '3' => 2,
        '4' => 2,

        '6' => 4, // refunded
        '7' => 5, // canceled
        '8' => 8, // chargeback

        // In dispute
        '9' => 10,
        '5' => 10,
    ];

    return $map[$status] ?? 5;  // Failed
}


/**
 * Normalize PagBank transactional status to generic gateway result.
 *
 * @param string $status PagBank charge status.
 *
 * @return string success|processing|error
 */
function pagbank_response_code(string $status): string
{
    $status = strtoupper(trim($status));

    if (in_array($status, ['PAID', 'AUTHORIZED', 'BYPASS'], true)) {
        return 'success';
    }

    if (in_array($status, ['WAITING', 'IN_ANALYSIS'], true)) {
        return 'processing';
    }

    return 'error';
}

/**
 * Resolve a user-friendly PagBank error message.
 *
 * This function maps technical gateway errors to safer and clearer messages
 * for the end user, while preserving the original gateway response for logs.
 *
 * @param array $errorMessages
 * @param string $status
 *
 * @return string
 */
function pagbank_resolve_error_message(array $errorMessages = [], string $status = ''): string
{
    $status = strtoupper(trim($status));

    if (!empty($errorMessages) && is_array($errorMessages))
    {
        foreach ($errorMessages as $message)
        {
            $parameter   = strtolower(trim((string)($message['parameter_name'] ?? '')));
            $description = strtolower(trim((string)($message['description'] ?? '')));
            $code        = (string)($message['code'] ?? '');
            $error       = (string)($message['error'] ?? '');

            /**
             * 1. HIGH PRIORITY (FIELD SPECIFIC)
             */
            // CPF / CNPJ do cliente
            if (
                $code === '40002' &&
                $parameter === 'customer.tax_id'
            ) {
                return 'O CPF ou CNPJ informado é inválido.';
            }

            // CPF titular cartão
            if (
                $parameter === 'card.holder.tax_id' ||
                $parameter === 'holder.tax_id'
            ) {
                return 'O CPF do titular do cartão não foi informado corretamente.';
            }

            // Número do cartão
            if ($parameter === 'card.number' || $parameter === 'number') {
                return 'O número do cartão informado é inválido.';
            }

             if (
                $code === '40002' &&
                $parameter === 'customer.tax_id'
            ) {
                return 'O CPF ou CNPJ informado é inválido.';
            }

            // CVV
            if ($parameter === 'security_code') {
                return 'O código de segurança do cartão está incorreto.';
            }

            // Expiração
            if ($parameter === 'card.exp_month' || $parameter === 'card.exp_year') {
                return 'A data de validade do cartão é inválida ou o cartão está expirado.';
            }

            /**
             * 2. MEDIUM PRIORITY (DESCRIPTION BASED)
             */
            if (strpos($description, 'cpf') !== false || strpos($description, 'cnpj') !== false) {
                return 'O CPF ou CNPJ informado é inválido.';
            }

            if (strpos($description, 'cvv') !== false) {
                return 'O código de segurança do cartão está incorreto.';
            }

            if (strpos($description, 'card number') !== false) {
                return 'O número do cartão informado é inválido.';
            }

            /**
             * Amount / installments
             */
            if ($code === '40009' || $parameter === 'amount' || strpos($description, 'amount') !== false) {
                return 'Não foi possível processar o pagamento por causa do valor informado.';
            }

            if ($code === '40010' || $parameter === 'installments' || strpos($description, 'installment') !== false) {
                return 'Não foi possível processar o pagamento com a quantidade de parcelas selecionada.';
            }

            /**
             * Null / required fields
             */
            if ($code === '40011' || strpos($description, 'must not be null') !== false || strpos($description, 'required') !== false) {
                return 'Não foi possível processar o pagamento porque alguns dados obrigatórios não foram informados.';
            }

            /**
             * Common decline scenarios
             */
            if ($code === '40012' || strpos($description, 'insufficient') !== false || strpos($description, 'funds') !== false) {
                return 'Seu cartão não possui limite suficiente para concluir esta compra.';
            }

            if ($code === '40013' || strpos($description, 'declined') !== false || strpos($description, 'not authorized') !== false || strpos($description, 'unauthorized') !== false) {
                return 'Seu cartão foi recusado pelo emissor. Tente novamente com outro cartão ou método de pagamento.';
            }

            if ($code === '40014' || strpos($description, '3ds') !== false || strpos($description, 'authentication') !== false) {
                return 'Não foi possível autenticar o pagamento com o banco emissor.';
            }

            /**
             * 3. GENERIC BY CODE
             */
            if ($code === '40001') {
                return 'Alguns dados obrigatórios não foram informados.';
            }

            if ($code === '40002')
            {
                if ($error == 'BRAND_NOT_FOUND') {
                    return 'Bandeira do cartão não encontrada. Digite o número do cartão novamente ou tente outro método de pagamento.';
                }

                return 'Algum dado informado é inválido. Verifique e tente novamente.';
            }

            if ($code === '40007') {
                return 'Não foi possível realizar a operação solicitada.';
            }

            if ($code === '40008') {
                return 'O endereço informado está incompleto ou inválido.';
            }

            if ($code === '40012') {
                return 'Seu cartão não possui limite suficiente.';
            }

            if ($code === '40013' || $code === '10002') {
                return 'Seu pagamento foi recusado pelo emissor.';
            }

            if ($code === '40014') {
                return 'Falha na autenticação do pagamento.';
            }

            if ($code === '10002') {
                return 'Não autorizado pelo emissor do cartão. Tente novamente com outro cartão ou método de pagamento.';
            }

            /**
             * 4. LAST RESORT (CUSTOMER GENERIC)
             */
            if ($parameter === 'customer' || strpos($parameter, 'customer.') === 0) {
                return 'Alguns dados do cliente estão incompletos ou inválidos.';
            }

            if ($parameter === 'address' || strpos($parameter, 'address.') === 0) {
                return 'O endereço informado está incompleto ou inválido.';
            }

            /**
             * 5. GATEWAY
             */
            if ($code === '50000') {
                return 'Estamos com instabilidade no processamento. Tente novamente.';
            }
        }
    }

    /**
     * FALLBACK POR STATUS
     */
    if (in_array($status, ['DECLINED', 'CANCELED', 'DENIED'], true)) {
        return 'Seu pagamento não foi autorizado.';
    }

    if (in_array($status, ['WAITING', 'PENDING', 'IN_ANALYSIS'], true)) {
        return 'Seu pagamento está em análise.';
    }

    return 'Não foi possível concluir o pagamento. Verifique os dados informados.';
}
