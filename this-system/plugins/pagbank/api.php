<?php
if (!isset($seg)) exit;

register_rest_route('pagbank-installments', [
  'methods'  => ['GET'],
  'callback' => function ()
  {
    $amount        = (int)($_GET['amount'] ?? 0); // cents
    $creditCardBin = preg_replace('/\D+/', '', (string)($_GET['credit_card_bin'] ?? ''));
    $maxInstallments = (int)($_GET['max_installments'] ?? 18);

    /**
     * Accept both names for compatibility:
     * - max_installments_no_interest
     * - max_interest_free_installments
     */
    $maxInstallmentsNoInterest = (int)($_GET['max_installments_no_interest']
      ?? $_GET['max_interest_free_installments']
      ?? 0);

    if ($amount < 500) {
      return [
        'code' => 'error',
        'msg'  => [
          'reason' => 'Minimum installment amount is R$5.00'
        ]
      ];
    }

    if (strlen($creditCardBin) < 6) {
      return [
        'code' => 'error',
        'msg'  => [
          'reason' => 'Invalid credit card BIN'
        ]
      ];
    }

    /**
     * PagBank does not accept value 1 here.
     * Valid values: 0 or greater than 1.
     */
    if ($maxInstallmentsNoInterest === 1) {
      $maxInstallmentsNoInterest = 0;
    }

    /**
     * Safety bounds
     */
    if ($maxInstallmentsNoInterest < 0) {
      $maxInstallmentsNoInterest = 0;
    }

    if ($maxInstallments < 1) {
      $maxInstallments = 1;
    }

    if ($maxInstallments > 18) {
      $maxInstallments = 18;
    }

    if ($maxInstallmentsNoInterest > $maxInstallments) {
      $maxInstallmentsNoInterest = $maxInstallments;
    }

    $params = [
      'payment_methods'              => 'CREDIT_CARD',
      'value'                        => $amount,
      'credit_card_bin'              => substr($creditCardBin, 0, 6),
      'max_installments'             => $maxInstallments,
      'max_installments_no_interest' => $maxInstallmentsNoInterest
    ];

    $response = pagbank_request(
      '/charges/fees/calculate',
      'GET',
      $params
    );

    if (($response['code'] ?? '') !== 'success') {
      return [
        'code' => 'error',
        'msg'  => [
          'reason' => 'PagBank request failed',
          'raw_response' => $response
        ]
      ];
    }

    $httpCode = (int)($response['http_code'] ?? 0);
    $json     = $response['response'] ?? [];

    if ($httpCode < 200 || $httpCode >= 300 || !is_array($json)) {
      return [
        'code' => 'error',
        'msg'  => [
          'reason' => 'PagBank returned invalid response',
          'http_code' => $httpCode,
          'raw_response_json' => $json
        ]
      ];
    }

    $plans = [];
    $brands = (array)($json['payment_methods']['credit_card'] ?? []);

    foreach ($brands as $brand => $brandData)
    {
      $installmentPlans = (array)($brandData['installment_plans'] ?? []);

      if (!empty($installmentPlans)) {
        $plans = $installmentPlans;
        break;
      }
    }

    return [
      'code' => 'success',
      'msg'  => [
        'plans' => $plans,
        'raw_response_json' => $json
      ]
    ];
  },
  'permission_callback' => '__return_true',
]);

register_rest_route('pagbank-public-key', [
  'methods'  => ['GET'],
  'callback' => function ()
  {
    $public_key = pagbank_get_public_key();

    if (empty($public_key)) {
      return [
        'code' => 'error',
        'msg'  => ['reason' => 'Could not fetch PagBank public key.'],
      ];
    }

    return [
      'code' => 'success',
      'msg'  => ['public_key' => $public_key],
    ];
  },
  'permission_callback' => '__return_true',
]);

register_rest_route('pagbank-3ds-session', [
  'methods'  => ['GET'],
  'callback' => function ()
  {
    global $current_user;

    $result = pagbank_create_3ds_session();

    if (($result['code'] ?? '') !== 'success') {
      return [
        'code' => 'error',
        'msg'  => ['reason' => 'Could not reach PagBank to start the 3DS session.'],
      ];
    }

    $httpCode = (int)($result['http_code'] ?? 0);
    $json     = (array)($result['response'] ?? []);

    if ($httpCode < 200 || $httpCode >= 300 || empty($json)) {
      return [
        'code' => 'error',
        'msg'  => [
          'reason'    => 'PagBank returned an invalid response for the 3DS session.',
          'http_code' => $httpCode,
        ],
      ];
    }

    // The exact response key isn't pinned down in PagBank's public docs at
    // the time of writing -- accept whichever of these it actually uses.
    $session = $json['session'] ?? $json['id'] ?? $json['token'] ?? null;

    if (empty($session)) {
      return [
        'code' => 'error',
        'msg'  => ['reason' => 'PagBank did not return a session value.'],
      ];
    }

    /**
     * Prefill data for the browser's authenticate3DS() call. Logged-in
     * users never see customer[name]/[email]/[phone] -- the checkout only
     * renders that block for guests -- so the JS has nothing to read from
     * the DOM in that case. Source it here instead, no user action needed.
     */
    $customer_name = trim(
        ($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? '')
    );

    return [
      'code' => 'success',
      'msg'  => [
        'session'  => $session,
        'env'      => get_system_info('pyrosales_is_sandbox') ? 'SANDBOX' : 'PROD',
        'customer' => [
          'name'  => $customer_name !== '' ? $customer_name : null,
          'email' => $current_user['email'] ?? null,
          'phone' => $current_user['phone'] ?? null,
        ],
      ],
    ];
  },
  'permission_callback' => '__return_true',
]);
