<?php
if (!isset($seg)) exit;

/**
 * Generate a subscription management form (insert/update by presence of ?id=).
 *
 * Mirrors manage_plans_form() (src/plan-manager.php) field-for-field for
 * every tb_plan_user_subscriptions column except id/created_at/updated_at.
 *
 * NAMING NOTE: the user_id selector was required to use this exact shape
 * (verbatim, as given):
 *
 *   input('selection_type', 'update', [
 *       'field_search' => 'users',
 *       'type' => 'search',
 *       'label' => 'Usuário',
 *       'name' => 'id',
 *       'Value' => $sub['user_id'],
 *   ])
 *
 * -- note its own `name` is 'id'. Since the record being edited here is
 * ALSO normally identified by a hidden `id` field (that's what
 * manage_plans_form() does for `tb_plans`), keeping both as 'id' would have
 * the selected user's id and the subscription's own id collide in the same
 * $_POST key. To honor the exact snippet above unchanged, this form's own
 * hidden identifier is posted as `subscription_id` instead -- see
 * manage_subscription_system() below, which reads `$_POST['id']` as the
 * chosen user_id and `$_POST['subscription_id']` as the row being updated.
 *
 * @param string $type_form The form mode, either 'insert' or 'update'.
 */
function manage_subscriptions_form(string $type_form = 'insert')
{
    $id = id_by_get();

    $sub = [];

    if (!empty($id)) {
        $sub = get_result("SELECT * FROM tb_plan_user_subscriptions WHERE id = '{$id}' LIMIT 1");
    }

    $type_form = $id ? 'update' : 'insert';

    if (count($sub) == 0) {
        $type_form = 'insert';
    }

    // Both this plugin's index.php and pyrosales' src/subscriptions/status.php
    // assign $GLOBALS['subscription_status'] (same status set, one keyed
    // 'title', the other 'name') -- whichever plugin's index.php loads last
    // wins the global, so read either key rather than assuming this
    // plugin's own shape is the one currently in memory.
    $status_options = array_map(
        fn($s) => ['value' => $s['slug'], 'display' => $s['title'] ?? $s['name'] ?? $s['slug']],
        $GLOBALS['subscription_status'] ?? []
    );

    $panel['hooks_out'][] = [
        'title'    => 'Listar',
        'url'      => get_url_page('subscriptions', 'full'),
        'color'    => 'outline-info',
        'pre_icon' => 'fas fa-list',
    ];

    if ($type_form == 'update')
    {
        $panel['hooks_out'][] = [
            'title'    => 'Apagar',
            'url'      => rest_api_route_url("delete-record?id={$id}&table=tb_plan_user_subscriptions&foreign_key=subscription_id"),
            'attr'     => 'data-controller: (delete);',
            'color'    => 'outline-danger',
            'pre_icon' => 'fas fa-trash',
        ];
    }

    echo crud_panel( $panel ?? [] );
    ?>

    <form class="col-md col-lg col-xl main-form" method="POST" data-send-ctrl-s data-send-without-reload action="<?= rest_api_route_url("manage-subscription?mode={$type_form}") ?>">
    <div class="card box-fields">
                <div class="card-body" id="container-card">
                    <div class="form-row">

                        <?php
                        echo
                        input(
                            'selection_type',
                            'update',
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'field_search' => 'users',
                                'type' => 'search',
                                'label' => 'Usuário',
                                'name' => 'id',
                                'Value' => $sub['user_id'] ?? '',
                            ]
                        ) .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Plano',
                                'type' => 'search',
                                'name' => 'plan_id',
                                'options_resolver' => "get_plans('list')",
                                'Value' => ($type_form == 'update') ? ($sub['plan_id'] ?? '') : '',
                                'Required' => true,
                            ]
                        ) .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Status',
                                'name' => 'status',
                                'Options' => $status_options,
                                'Value' => ($type_form == 'update') ? ($sub['status'] ?? 'pending') : 'pending',
                                'Required' => true,
                            ]
                        ) .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'type' => 'switch',
                                'size' => 'col-md-6 col-lg-6',
                                'name' => 'settings',
                                'Options' => [
                                    [
                                        'value'   => '1',
                                        'name'    => 'auto_renew',
                                        'display' => 'Renovação automática',
                                        'checked' => ($type_form == 'update') ? (int)($sub['auto_renew'] ?? 0) : 1,
                                    ],
                                    [
                                        'value'   => '1',
                                        'name'    => 'bypass_payment',
                                        'display' => 'Bypass de pagamento (gera o pedido já pago, sem cobrar)',
                                        'checked' => ($type_form == 'update') ? (int)($sub['bypass_payment'] ?? 0) : 0,
                                    ],
                                ],
                            ]
                        ) .
                        "<fieldset class='col-12'>
                            <h4>Ciclo</h4>
                        </fieldset>" .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'number',
                                'label' => 'Quantidade de ciclos',
                                'name' => 'cycles_quantity',
                                'disabled' => true,
                                'Value' => ($type_form == 'update') ? ($sub['cycles_quantity'] ?? 0) : 0,
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'date',
                                'label' => 'Iniciada em',
                                'name' => 'started_at',
                                'Value' => ($type_form == 'update') ? ($sub['started_at'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'date',
                                'label' => 'Fim do teste grátis',
                                'name' => 'trial_ends_at',
                                'Value' => ($type_form == 'update') ? ($sub['trial_ends_at'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'date',
                                'label' => 'Encerrada em',
                                'name' => 'ended_at',
                                'Value' => ($type_form == 'update') ? ($sub['ended_at'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'date',
                                'label' => 'Período atual - início',
                                'name' => 'current_period_start',
                                'Value' => ($type_form == 'update') ? ($sub['current_period_start'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'date',
                                'label' => 'Período atual - fim',
                                'name' => 'current_period_end',
                                'Value' => ($type_form == 'update') ? ($sub['current_period_end'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'date',
                                'label' => 'Próxima cobrança',
                                'name' => 'next_billing_at',
                                'Value' => ($type_form == 'update') ? ($sub['next_billing_at'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'date',
                                'label' => 'Fim da carência',
                                'name' => 'grace_ends_at',
                                'disabled' => true,
                                'Value' => ($type_form == 'update') ? ($sub['grace_ends_at'] ?? '') : '',
                            ]
                        ) .
                        "<fieldset class='col-12'>
                            <h4>Cobrança</h4>
                        </fieldset>" .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Statement descriptor',
                                'name' => 'statement_descriptor',
                                'Value' => ($type_form == 'update') ? ($sub['statement_descriptor'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'type' => 'number',
                                'label' => 'Tentativas de renovação',
                                'name' => 'renewal_attempts_count',
                                'disabled' => true,
                                'Value' => ($type_form == 'update') ? ($sub['renewal_attempts_count'] ?? 0) : 0,
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'type' => 'datetime-local',
                                'label' => 'Última tentativa de renovação',
                                'name' => 'last_renewal_attempt_at',
                                'disabled' => true,
                                'Value' => ($type_form == 'update') ? ($sub['last_renewal_attempt_at'] ?? '') : '',
                            ]
                        ) .
                        "<fieldset class='col-12'>
                            <h4>Cancelamento</h4>
                        </fieldset>" .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Origem do cancelamento (sistema)',
                                'name' => 'cancel_flow',
                                'disabled' => true,
                                'Value' => ($type_form == 'update') ? ($sub['cancel_flow'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Motivo selecionado pelo cliente',
                                'name' => 'cancel_reason',
                                'Options' => get_subscription_cancel_reason_options(),
                                'Value' => ($type_form == 'update') ? ($sub['cancel_reason'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Probabilidade de retornar',
                                'name' => 'probability_return',
                                'Options' => get_subscription_return_likelihood_options(),
                                'Value' => ($type_form == 'update') ? ($sub['probability_return'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'textarea',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Comentário do cliente',
                                'attributes' => 'rows:(1);',
                                'name' => 'user_cancel_detail',
                                'Value' => ($type_form == 'update') ? ($sub['user_cancel_detail'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'type' => 'datetime-local',
                                'label' => 'Cancelada em',
                                'name' => 'canceled_at',
                                'Value' => ($type_form == 'update') ? ($sub['canceled_at'] ?? '') : '',
                            ]
                        ) .
                        input(
                            'submit_button',
                            $type_form,
                            [
                                'size' => 'col-12',
                                'class' => 'btn btn-st',
                                'Value' => ($type_form == 'update') ? 'Update' : 'Create'
                            ]
                        );
                        ?>

                    </div>
                </div>
    </div>

    <?php
    if ($type_form == 'update') {
        echo input('hidden', $type_form, [
            'name' => 'subscription_id',
            'Value' => $id
        ]);
    }
    ?>
    </form>

    <?php
    unset($_SESSION['FormData']);
}


/**
 * Manage subscription data in the system (insert/update tb_plan_user_subscriptions).
 *
 * See manage_subscriptions_form()'s docblock for why the selected user_id
 * arrives as `$data['id']` and the row's own id (update mode only) arrives
 * as `$data['subscription_id']`.
 *
 * @param array  $data  The submitted form data ($_POST).
 * @param string $mode  The operation mode: insert or update.
 * @param bool   $debug Enable debug mode.
 *
 * @return array
 */
function manage_subscription_system(array $data, string $mode, bool $debug = false)
{
    $error      = false;
    $valid_data = $data;
    $msg_type   = 'toast';

    $permission = load_permission('plan-manager', 'custom');
    if (!$permission) {
        return invalid_permission_response();
    }

    if ($mode == 'insert') {
        $verifier = 'inserted_id';
    } elseif ($mode == 'update') {
        $verifier = 'affected_rows';
    } else {
        $error = true;
    }

    // Nullable date/datetime columns: an empty string from the form must
    // become NULL, not '' (invalid for a DATE/DATETIME column).
    $nullable_if_empty = [
        'trial_ends_at', 'ended_at', 'current_period_start', 'current_period_end',
        'next_billing_at', 'grace_ends_at', 'last_renewal_attempt_at', 'canceled_at',
        'cancel_flow', 'cancel_reason', 'probability_return', 'user_cancel_detail',
        'statement_descriptor', 'user_payment_method_id',
    ];
    foreach ($nullable_if_empty as $field) {
        if (isset($valid_data[$field]) && trim((string)$valid_data[$field]) === '') {
            $valid_data[$field] = null;
        }
    }

    if ($error) {
        $_SESSION['FormData'] = $data;
    }

    else
    {
        $args = [
            // 'id' here is the searched USER's id (see docblock) -- not this row's own id.
            'user_id'                 => (int)($valid_data['id'] ?? 0),
            'plan_id'                 => (int)($valid_data['plan_id'] ?? 0),
            'status'                  => $valid_data['status'] ?? 'pending',
            'auto_renew'              => !empty($valid_data['auto_renew']) ? 1 : 0,
            'bypass_payment'          => !empty($valid_data['bypass_payment']) ? 1 : 0,
            // 'cycles_quantity'         => (int)($valid_data['cycles_quantity'] ?? 0),
            'started_at'              => $valid_data['started_at'] ?? null,
            'trial_ends_at'           => $valid_data['trial_ends_at'] ?? null,
            'ended_at'                => $valid_data['ended_at'] ?? null,
            'current_period_start'    => $valid_data['current_period_start'] ?? null,
            'current_period_end'      => $valid_data['current_period_end'] ?? null,
            'next_billing_at'         => $valid_data['next_billing_at'] ?? null,
            // 'grace_ends_at'           => $valid_data['grace_ends_at'] ?? null,
            'statement_descriptor'    => $valid_data['statement_descriptor'] ?? null,
            // 'user_payment_method_id'  => $valid_data['user_payment_method_id'] ?? null,
            // 'renewal_attempts_count'  => (int)($valid_data['renewal_attempts_count'] ?? 0),
            // 'last_renewal_attempt_at' => $valid_data['last_renewal_attempt_at'] ?? null,
            // 'cancel_flow'             => $valid_data['cancel_flow'] ?? null,
            'cancel_reason'           => $valid_data['cancel_reason'] ?? null,
            'probability_return'      => $valid_data['probability_return'] ?? null,
            'user_cancel_detail'      => $valid_data['user_cancel_detail'] ?? null,
            'canceled_at'             => $valid_data['canceled_at'] ?? null,
        ];

        if (empty($args['user_id']) || empty($args['plan_id']))
        {
            $_SESSION['FormData'] = $data;
            $error = true;
            $msg   = alert_message('ER_TO_' . strtoupper($mode), $msg_type);
        }
        else
        {
            if ($mode == 'insert') {
                $args['created_at'] = 'NOW()';
            } elseif ($mode == 'update') {
                $args['updated_at'] = 'NOW()';
                $args['data']       = $args;
                $args['where']      = where_equal_id((int)($valid_data['subscription_id'] ?? 0));
            }

            $mode('tb_plan_user_subscriptions', $args, false, $debug);

            if ($verifier())
            {
                unset($_SESSION['FormData']);

                $subscription_id = ($mode == 'insert')
                    ? inserted_id()
                    : (int)($valid_data['subscription_id'] ?? 0);

                $msg = alert_message('SC_TO_' . strtoupper($mode), $msg_type);
            }
            else
            {
                $_SESSION['FormData'] = $data;
                $msg = alert_message('ER_TO_' . strtoupper($mode), $msg_type);
            }
        }
    }

    $res = [
        'code'   => !$error ? 'success' : 'error',
        'detail' => [
            'type' => $msg_type,
            'msg'  => $msg ?? '',
        ],
    ];

    if ($mode == 'insert' && !empty($subscription_id)) {
        $res['redirect'] = get_url_page('subscription-manager', 'full') . "?id={$subscription_id}";
    }

    return $res;
}
