<?php
if (!isset($seg)) exit;

/**
 * Generate a plan management form.
 *
 * @param string $type_form The form mode, either 'insert' or 'update'.
 *
 * @return int
 */
function manage_plans_form(string $type_form = 'insert', array $subscription_target_audience = [])
{
    $page = $GLOBALS['page'];

    $id = id_by_get();

    $plan = [];
    $plan_role_ids = [];

    if (!empty($id)) {
        $plan = get_result("SELECT * FROM tb_plans WHERE id = '{$id}' AND user_id IS NULL LIMIT 1");
    }

    $type_form = $id
        ? 'update'
        : 'insert';

    if (count($plan) == 0) {
        $type_form = 'insert';
    }

    if ($type_form == 'update') {
        $plan_role_ids = get_plan_role_ids((int)$id);
    }

    $panel['hooks_out'][] = [
        'title' => 'Listar',
        'url'  => get_url_page('plans', 'full'),
        'color' => 'outline-info',
        'pre_icon' => 'fas fa-list',
    ];

    if ($type_form == 'update')
    {
        $panel['hooks_out'][] = [
            'title' => 'Duplicar',
            'url'  => rest_api_route_url("duplicate-record?id={$id}&table=tb_plans&foreign_key=plan_id"),
            'attr'  => 'data-controller: (duplicate);',
            'color' => 'outline-info',
            'pre_icon' => 'fas fa-copy',
        ];

        $panel['hooks_out'][] = [
            'title' => 'Apagar',
            'url'  => rest_api_route_url("delete-record?id={$id}&table=tb_plans&foreign_key=plan_id"),
            'attr'  => 'data-controller: (delete);',
            'color' => 'outline-danger',
            'pre_icon' => 'fas fa-trash',
        ];
    }

    echo crud_panel( $panel ?? [] );
    ?>

    <form class="col-md col-lg col-xl main-form" method="POST" data-send-ctrl-s data-send-without-reload action="<?= rest_api_route_url("manage-plan?mode={$type_form}") ?>">
    <div class="card box-fields">
                <div class="card-body" id="container-card">
                    <div class="form-row">

                        <?php
                        echo
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Name',
                                'name' => 'name',
                                'Value' => ($type_form == 'update') ? $plan['name'] : '',
                                'Required' => true
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Slug',
                                'name' => 'slug',
                                'Value' => ($type_form == 'update') ? $plan['slug'] : '',
                                'Alert' => 'Changing the slug of an existing plan may break existing flows.',
                                'Required' => true
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-2',
                                'type' => 'number',
                                'label' => 'Order',
                                'name' => 'order_reg',
                                'Value' => ($type_form == 'update') ? $plan['order_reg'] : '',
                            ]
                        ) .
                        input(
                            'status_selector',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-2',
                                'function_process' => 'general_status',
                                'name' => 'status_id',
                                'Value' => ($type_form == 'update') ? $plan['status_id'] : 1,
                                'Required' => true
                            ]
                        ) .
                        input(
                            'textarea',
                            $type_form,
                            [
                                'size' => 'col-12',
                                'label' => 'Description',
                                'name' => 'description',
                                'Value' => ($type_form == 'update') ? $plan['description'] : '',
                            ]
                        ) .
                        "<fieldset class='col-12'>
                            <h4>Pricing</h4>
                        </fieldset>" .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Currency',
                                'name' => 'currency',
                                'Value' => ($type_form == 'update') ? $plan['currency'] : 'BRL',
                                'Required' => true
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-2',
                                'type' => 'text',
                                'class' => 'mask-money',
                                'step' => '0.00',
                                'label' => 'Regular price',
                                'name' => 'regular_price',
                                'Value' => ($type_form == 'update') ? $plan['regular_price'] : '',
                                'Required' => true
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-2',
                                'type' => 'text',
                                'class' => 'mask-money',
                                'step' => '0.00',
                                'label' => 'Sale price',
                                'name' => 'sale_price',
                                'Value' => ($type_form == 'update') ? $plan['sale_price'] : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'Alert' => 'Defines how many interval units has sale price (discount). After this charge with regular price. If it\'s = 0 then always apply the discount',
                                'type' => 'number',
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Sale price cycles',
                                'name' => 'sale_price_cycles',
                                'Value' => ($type_form == 'update') ? $plan['sale_price_cycles'] : '0',
                                'Required' => true
                            ]
                        ) .
                        "<fieldset class='col-12'>
                            <h4>Periods</h4>
                        </fieldset>" .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'label' => 'Interval unit',
                                'name' => 'interval_unit',
                                'Options' => [
                                    ['value' => 'day', 'display' => 'Day'],
                                    ['value' => 'week', 'display' => 'Week'],
                                    ['value' => 'month', 'display' => 'Month'],
                                    ['value' => 'year', 'display' => 'Year'],
                                    ['value' => 'lifetime', 'display' => 'Lifetime'],
                                ],
                                'Value' => ($type_form == 'update') ? $plan['interval_unit'] : 'month',
                                'Required' => true
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'Alert' => 'Defines how many interval units make up one billing cycle. Example: 1 month, 3 months, or 12 months.',
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'number',
                                'label' => 'Interval count',
                                'name' => 'interval_count',
                                'Value' => ($type_form == 'update') ? $plan['interval_count'] : 1,
                                'Required' => true
                            ]
                        ) . input(
                            'selection_type',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-3',
                                'label' => 'Target audience',
                                'name' => 'target_audience',
                                'Options' => $subscription_target_audience,
                                'Value' => ($type_form == 'update') ? $plan['target_audience'] : '',
                                // 'Required' => true
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'Alert' => 'Sets how many days the customer can use the plan for free before the first charge is applied.',
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'number',
                                'label' => 'Trial days',
                                'name' => 'trial_days',
                                'Value' => ($type_form == 'update') ? $plan['trial_days'] : 0,
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'Alert' => 'Defines how many extra days the subscription can remain active after a failed payment or expiration before access is fully revoked.',
                                'size' => 'col-md-6 col-lg-3',
                                'type' => 'number',
                                'label' => 'Grace days',
                                'name' => 'grace_days',
                                'Value' => ($type_form == 'update') ? $plan['grace_days'] : 0,
                            ]
                        ) .
                        "<fieldset class='col-12'>
                            <h4>Payment</h4>
                        </fieldset>" .
                        input(
                            'basic',
                            $type_form,
                            [
                                'Alert' => 'Controls who absorbs gateway or transaction fees for this plan, such as the merchant or the customer.',
                                'size' => 'col-md-6 col-lg-3',
                                'label' => 'Fee mode',
                                'name' => 'fee_mode',
                                'Value' => ($type_form == 'update') ? $plan['fee_mode'] : 'merchant',
                            ]
                        ) .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'type' => 'switch',
                                'size' => 'col-md-6 col-lg-3',
                                'name' => 'seetings',
                                'Options' => [
                                    [
                                        'value' => '1',
                                        'name' => 'auto_renew',
                                        'display' => 'Enable auto renewal',
                                        'checked' => ($type_form == 'update') ? (int)$plan['auto_renew'] : 1,
                                    ],
                                    [
                                        'value' => '1',
                                        'display' => 'Proposal lock',
                                        'name' => 'allows_price_lock',
                                        'checked' => ($type_form == 'update') ? (int)$plan['allows_price_lock'] : 1,
                                    ],
                                ],
                            ]
                        ) .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'label' => 'Accpeted payment methods',
                                'name' => 'accepted_payment_methods[]',
                                'type' => 'switch',
                                'variation' => 'inline',
                                'Options' => format_payment_gateways("is_settings_form_free", ($plan['accepted_payment_methods'] ?? []))
                            ]
                        ) .
                        "<fieldset class='col-12'>
                            <h4>Advanced</h4>
                        </fieldset>" .
                        input(
                            'textarea',
                            $type_form,
                            [
                                'Alert' => 'Optional callback function executed after the plan is activated. Use this for custom business rules or post-activation actions.',
                                'size' => 'col-md-6 col-lg-6',
                                'label' => 'Activation function',
                                'attributes' => 'rows:(1);',
                                'name' => 'activation_function',
                                'Value' => ($type_form == 'update') ? $plan['activation_function'] : '',
                            ]
                        ) .
                        input(
                            'textarea',
                            $type_form,
                            [
                                'Alert' => 'Optional callback function executed after the plan is activated. Use this for custom business rules or post-activation actions.',
                                'size' => 'col-md-6 col-lg-6',
                                'label' => 'Deactivation function',
                                'attributes' => 'rows:(1);',
                                'name' => 'deactivation_function',
                                'Value' => ($type_form == 'update') ? $plan['deactivation_function'] : '',
                            ]
                        ) .
                        input(
                            'basic',
                            $type_form,
                            [
                                'size' => 'col-md-6 col-lg-4',
                                'label' => 'Badge label',
                                'name' => 'badge_label',
                                'Value' => ($type_form == 'update') ? $plan['badge_label'] : '',
                                // 'Required' => true
                            ]
                        ) .
                        input(
                            'selection_type',
                            $type_form,
                            [
                                'size' => 'col-6',
                                'label' => 'Apply roles',
                                'name' => 'roles[]',
                                'type' => 'checkbox',
                                'variation' => 'balloons',
                                'options_resolver' => "get_roles('list')",
                                'Value' => $plan_role_ids,
                                // 'Required' => true
                            ]
                        ) .
                        // input(
                        //     'selection_type',
                        //     $type_form,
                        //     [
                        //         'type' => 'checkbox',
                        //         'size' => 'col-md-6 col-lg-4',
                        //         'name' => 'is_visible',
                        //         'Options' => [
                        //             ['value' => '1', 'display' => 'Visible in listings'],
                        //         ],
                        //         'Value' => ($type_form == 'update') ? (int)$plan['is_visible'] : 1,
                        //     ]
                        // ) .
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
            'name' => 'id',
            'Value' => $id
        ]);
    }
    ?>
    </form>

    <?php
    unset($_SESSION['FormData']);
}


/**
 * Manage plan data in the system.
 *
 * @param array  $data  The submitted form data.
 * @param string $mode  The operation mode: insert or update.
 * @param bool   $debug Enable debug mode.
 *
 * @return array
 */
function manage_plan_system(array $data, string $mode, bool $debug = false)
{
    $error = false;
    $valid_data = $data;
    $msg_type = 'toast';

    $permission = load_permission('plan-manager', 'custom');
    if (!$permission) {
      return invalid_permission_response();
    }

    if ($mode == 'insert') {
        $verifier = 'inserted_id';
    }

    elseif ($mode == 'update') {
        $verifier = 'affected_rows';
    }

    else {
        $error = true;
    }

    $regular_price = ($valid_data['regular_price'] ?? 0);
    $sale_price = (($valid_data['sale_price'] ?? '') !== '' ? $valid_data['sale_price'] : 0);

    $regular_price = DECIMAL($regular_price);
    $sale_price = DECIMAL($sale_price);

    if ($sale_price >= $regular_price) {
        $sale_price = 0;
    }


    if ($error) {
        $_SESSION['FormData'] = $data;
    }

    else
    {
        $args = [
            'name' => $valid_data['name'] ?? '',
            'slug' => $valid_data['slug'] ?? '',
            'order_reg' => $valid_data['order_reg'] ?? null,
            'description' => $valid_data['description'] ?? '',
            'badge_label' => $valid_data['badge_label'] ?? '',
            'currency' => $valid_data['currency'] ?? 'BRL',
            'accepted_payment_methods' => $valid_data['accepted_payment_methods'] ?? [],
            // 'regular_price' => DECIMAL($regular_price),
            // 'sale_price' => DECIMAL($sale_price),
            'sale_price_cycles' => $valid_data['sale_price_cycles'] ?? 0,
            'regular_price' => ($regular_price),
            'sale_price' => ($sale_price),
            'interval_unit' => $valid_data['interval_unit'] ?? 'month',
            'interval_count' => $valid_data['interval_count'] ?? 1,
            'trial_days' => $valid_data['trial_days'] ?? 0,
            'target_audience' => $valid_data['target_audience'] ?? '',
            'grace_days' => $valid_data['grace_days'] ?? 0,
            'auto_renew' => !empty($valid_data['auto_renew']) ? 1 : 0,
            'is_visible' => !empty($valid_data['is_visible']) ? 1 : 0,
            'fee_mode' => $valid_data['fee_mode'] ?? 'merchant',
            'activation_function' => $valid_data['activation_function'] ?? '',
            'deactivation_function' => $valid_data['deactivation_function'] ?? '',
            'allows_price_lock' => !empty($valid_data['allows_price_lock']) ? 1 : 0,
            'status_id' => $valid_data['status_id'] ?? 1,
            // 'user_id' => null,
        ];

        if ($mode == 'insert')
        {
            $args['created_at'] = 'NOW()';
        }

        else if ($mode == 'update')
        {
            $args['updated_at'] = 'NOW()';
            $args['data'] = $args;
            $args['where'] = where_equal_id($valid_data['id']);
        }

        $mode('tb_plans', $args, false, $debug);

        if ($verifier())
        {
            unset($_SESSION['FormData']);

            $plan_id = ($mode == 'insert')
                ? inserted_id()
                : (int)$valid_data['id'];

            sync_plan_roles($plan_id, $valid_data['roles'] ?? [], $debug);

            $msg = alert_message("SC_TO_" . strtoupper($mode), $msg_type);
        }

        else
        {
            $_SESSION['FormData'] = $data;
            $msg = alert_message("ER_TO_" . strtoupper($mode), $msg_type);
        }
    }

    $res = [
        'code' => !$error ? 'success' : 'error',
        'detail' => [
            'type' => $msg_type,
            'msg' => $msg ?? '',
        ],
    ];

    if ($mode == 'insert' && !empty($plan_id)) {
        $res['redirect'] = get_url_page('manage-plans', 'full') . "?id={$plan_id}";
    }

    return $res;
}
