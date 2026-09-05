<?php
if(!isset($seg)) exit;

/**
 * Register a REST API route for manage a plan.
 *
 * This code registers a REST API route named 'manage-plan'.
 */
register_rest_route('manage-plan', [
  'methods' => ['POST', 'GET'],
  'callback' => function()
  {
    global $seg;

    load_plan_form();

    return manage_plan_system($_POST, $_GET['mode']);
  },
  'permission_callback' => '__return_true',
]);


/**
 * REST route: order-plans
 *
 * Receives the same `order[0..N]` payload shape order-record uses (ids in
 * their new position, 1-based — position 1 is the highest). Only reorders
 * plans that belong to the SAME group as the first dragged plan (matching
 * target_audience + interval_count + interval_unit) — looked up from the
 * DB, not trusted from the request, so a row can't be moved into a
 * different group's ranking even if the client sent a mixed list.
 *
 * In normal use the whole `order[]` array only ever contains ids from one
 * group anyway (each rendered segment table only has that group's rows to
 * drag in the first place) — the group check here is a safety net, not the
 * primary mechanism.
 */
register_rest_route('order-plans', [
  'methods'  => 'POST',
  'callback' => function ()
  {
    $order = $_POST['order'] ?? [];
    if (empty($order) || !is_array($order)) {
      return ['code' => 'error', 'message' => 'No order provided.'];
    }

    $permission = load_permission('plan-manager', 'custom');
    if (!$permission) {
      return invalid_permission_response();
    }

    // Identify the group from the first dragged plan.
    $first_id  = (int) reset($order);
    $reference = get_result("SELECT target_audience, interval_count, interval_unit FROM tb_plans WHERE id = '{$first_id}' LIMIT 1");

    if (empty($reference)) {
      return ['code' => 'error', 'message' => 'Plan not found.'];
    }

    $target_audience = addslashes($reference['target_audience']);
    $interval_count  = (int) $reference['interval_count'];
    $interval_unit   = addslashes($reference['interval_unit']);

    $position = 1;
    $updated  = 0;

    foreach ($order as $id)
    {
      $id = (int) $id;
      if (!$id) continue;

      $result = query_it("
        UPDATE tb_plans
        SET order_reg = '{$position}'
        WHERE id = '{$id}'
          AND target_audience = '{$target_audience}'
          AND interval_count = '{$interval_count}'
          AND interval_unit = '{$interval_unit}'
      ");

      if ($result) $updated++;
      $position++;
    }

    return ['code' => 'success', 'updated' => $updated];
  },
  'permission_callback' => '__return_true',
]);


/**
 * REST route: manage-subscription
 *
 * Insert/update for the admin "Subscription manager" page (custom-pages/subscription-manager.php).
 * Mirrors manage-plan's own route shape exactly.
 */
register_rest_route('manage-subscription', [
  'methods' => ['POST', 'GET'],
  'callback' => function()
  {
    global $seg;

    load_subscription_form();

    return manage_subscription_system($_POST, $_GET['mode']);
  },
  'permission_callback' => '__return_true',
]);


/**
 * REST route: toggle-subscription-auto-renew
 *
 * Flips tb_plan_user_subscriptions.auto_renew. Used by both the admin "All
 * subscriptions" listing and the customer "Minhas assinaturas" listing
 * (assets/scripts/subscriptions.js) -- allowed for an admin (plan-manager
 * permission) or the subscription's own owner.
 */
register_rest_route('toggle-subscription-auto-renew', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    $payload = $_POST ?? [];
    $id = (int) ($payload['id'] ?? 0);

    if ($id <= 0) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Assinatura inválida.']];
    }

    $permission = load_permission('plan-manager', 'custom');
    if (!$permission) {
      return invalid_permission_response();
    }

    $subscription = get_subscription($id);
    if (empty($subscription['id'])) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Assinatura não encontrada.']];
    }

    $new_value = empty($subscription['auto_renew']) ? 1 : 0;

    update('tb_plan_user_subscriptions', [
      'data'  => ['auto_renew' => $new_value, 'updated_at' => 'NOW()'],
      'where' => where_equal_id($id),
    ]);

    return ['code' => 'success', 'auto_renew' => $new_value];
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);


/**
 * REST route: update-subscription-field
 *
 * Merges what used to be two separate routes (update-subscription-
 * statement-descriptor / update-subscription-payment-method) into one --
 * which field(s) get updated depends on which key(s) arrive in the
 * payload: `statement_descriptor` (Nome fatura) and/or
 * `user_payment_method_id` (payment method; empty means "use the system
 * default payment method", i.e. NULL on the row, same as
 * charge_subscription_with_default_payment_method()'s own fallback --
 * pyrosales, src/subscriptions/renewal.php -- already expects). Both can
 * arrive together in ONE request (a single save on ONE update()) -- used
 * by the "Atualizar pagamento" modal on the customer-facing "Minhas
 * assinaturas" d2c_single_plan view (custom-pages/my-subscriptions.php) --
 * or one at a time, as the admin/multi-plan listing's inline edit already
 * does (blur on the descriptor input, click on the payment-method dropdown
 * -- assets/scripts/my-subscriptions.js).
 *
 * Allowed for an admin (plan-manager permission) OR the subscription's own
 * owner -- previously only the admin permission was actually checked here
 * despite this route backing the CUSTOMER-facing listing, so no real
 * customer could ever save either field through it.
 *
 * user_payment_method_id is checked against tb_user_payment_methods.user_id
 * before being saved -- a card id alone is never enough; it must belong to
 * this same subscription's own user.
 *
 * `reload_on_success` (any truthy value) makes the response carry
 * `redirect: '{force_reload}'` so a plain page (not one of the two
 * AJAX-driven UIs above, which apply their own optimistic DOM update and
 * must NOT reload) re-renders with the fresh values -- the modal's form
 * sets this as a hidden field.
 */
register_rest_route('update-subscription-field', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    global $current_user;

    $payload = $_POST ?? [];
    $id = (int) ($payload['id'] ?? 0);

    if ($id <= 0) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Assinatura inválida.']];
    }

    $subscription = get_subscription($id);
    if (empty($subscription['id'])) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Assinatura não encontrada.']];
    }

    $is_admin = (bool) load_permission('plan-manager', 'custom');
    $is_owner = !empty($current_user['id']) && (int) $current_user['id'] === (int) $subscription['user_id'];

    if (!$is_admin && !$is_owner) {
      return invalid_permission_response();
    }

    $set = [];

    if (array_key_exists('statement_descriptor', $payload))
    {
      $statement_descriptor = trim((string) ($payload['statement_descriptor'] ?? ''));
      $set['statement_descriptor'] = $statement_descriptor !== '' ? $statement_descriptor : null;
    }

    if (array_key_exists('user_payment_method_id', $payload))
    {
      $user_payment_method_id = trim((string) ($payload['user_payment_method_id'] ?? ''));

      if ($user_payment_method_id !== '')
      {
        // The card being assigned must belong to THIS subscription's own
        // user -- previously nothing enforced that, so any card id posted
        // here (including another user's) was accepted as-is.
        $card = get_result("
          SELECT id
          FROM tb_user_payment_methods
          WHERE id = '" . (int) $user_payment_method_id . "'
            AND user_id = '" . (int) $subscription['user_id'] . "'
          LIMIT 1
        ");

        if (empty($card['id'])) {
          return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Este cartão não pertence a este usuário.']];
        }
      }

      $set['user_payment_method_id'] = $user_payment_method_id !== '' ? (int) $user_payment_method_id : null;
    }

    if (empty($set)) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Nenhum campo para atualizar.']];
    }

    $set['updated_at'] = 'NOW()';

    update('tb_plan_user_subscriptions', [
      'data'  => $set,
      'where' => where_equal_id($id),
    ]);

    $updated_both = array_key_exists('statement_descriptor', $set) && array_key_exists('user_payment_method_id', $set);

    $msg_key = $updated_both
      ? 'SC_TO_UPDATE_PAYMENT_SETTINGS'
      : (array_key_exists('user_payment_method_id', $set) ? 'SC_TO_UPDATE_PAYMENT_METHOD' : 'SC_TO_UPDATE_STATEMENT_DESCRIPTOR');

    $res = [
      'code' => 'success',
      'detail' => [
        'type' => 'toast',
        'msg' => alert_message($msg_key, 'toast'),
      ],
    ];

    if (!empty($payload['reload_on_success'])) {
      $res['redirect'] = '{force_reload}';
    }

    return $res;
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);


/**
 * REST route: cancel-subscription
 *
 * Backs custom-pages/cancel-subscription.php. Deliberately does NOT call
 * pyrosales' finalize_plan_subscription() here -- per the request, this
 * must not take the customer's benefits away immediately or change
 * `status`, only turn auto_renew off, so they keep access through the
 * period they already paid for. The actual 'canceled' transition happens
 * later, once that period really ends -- see close_non_renewing_plan_subscriptions()
 * (pyrosales, src/subscriptions/cancel.php), wired into the plan
 * subscriptions cron -- which reads cancel_flow/cancel_reason/
 * user_cancel_detail/probability_return stored here so the customer's
 * original answers survive into that later finalize_plan_subscription()
 * call instead of being overwritten by generic ones.
 *
 * The form's three answers are saved as their own columns (not folded into
 * one free-text field): `cancel_reason` is the selected reason category,
 * `user_cancel_detail` is the free-text comment, `probability_return` is
 * the return-likelihood pick. `cancel_flow` records HOW the subscription
 * got here (this route vs. an admin action vs. a failed renewal) -- see
 * get_subscription_cancel_reason_options()/get_subscription_return_likelihood_options()
 * (src/ui.php) for the value/label vocabularies.
 */
register_rest_route('cancel-subscription', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    global $current_user;

    if (empty($current_user['id'])) {
      return invalid_permission_response();
    }

    $subscription_id = (int) ($_POST['subscription_id'] ?? 0);
    if ($subscription_id <= 0) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Assinatura inválida.']];
    }

    $subscription = get_subscription($subscription_id);
    if (empty($subscription['id']) || (int) $subscription['user_id'] !== (int) $current_user['id']) {
      return invalid_permission_response();
    }

    if (!in_array($subscription['status'], ['active', 'trialing', 'past_due'], true)) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Esta assinatura não pode ser cancelada no status atual.']];
    }

    $reason_category   = trim((string) ($_POST['reason_category'] ?? ''));
    $reason_text       = trim((string) ($_POST['reason'] ?? ''));
    $return_likelihood = trim((string) ($_POST['return_likelihood'] ?? ''));

    update('tb_plan_user_subscriptions', [
      'data'  => [
        'auto_renew'         => 0,
        'cancel_flow'        => 'customer_requested_auto_renew_off',
        'cancel_reason'      => $reason_category !== '' ? $reason_category : null,
        'user_cancel_detail' => $reason_text !== '' ? $reason_text : null,
        'probability_return' => $return_likelihood !== '' ? $return_likelihood : null,
        'updated_at'         => 'NOW()',
      ],
      'where' => where_equal_id($subscription_id),
    ]);

    return [
      'code'     => 'success',
      'detail'   => ['type' => 'toast', 'msg' => 'Renovação automática desativada.'],
      'redirect' => get_url_page('cancel-subscription', 'full') . "?id={$subscription_id}&canceled=1",
    ];
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);


/**
 * REST route: cancel-subscription-admin
 *
 * Wires up the admin "All subscriptions" listing's "cancelar" button
 * (render_subscription_admin_actions_html(), src/ui.php) -- previously
 * rendered visible but disabled. Unlike the customer-facing
 * `cancel-subscription` route above (which only turns auto_renew off and
 * leaves the customer with access through their paid period), this one is
 * an immediate admin action: it calls pyrosales' finalize_plan_subscription()
 * directly with final_status = 'canceled', right away -- benefits are
 * removed now, on purpose, since this is the admin acting on the customer's
 * behalf (support request, fraud, etc.), not the customer's own
 * self-service flow.
 */
register_rest_route('cancel-subscription-admin', [
  'methods'  => ['POST'],
  'callback' => function ()
  {
    $permission = load_permission('subscription-manager', 'custom');
    if (!$permission) {
      return invalid_permission_response();
    }

    $subscription_id = (int) ($_POST['subscription_id'] ?? 0);
    if ($subscription_id <= 0) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Assinatura inválida.']];
    }

    $subscription = get_subscription($subscription_id);
    if (empty($subscription['id'])) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Assinatura não encontrada.']];
    }

    $cancelable_statuses = ['pending', 'active', 'trialing', 'past_due', 'paused'];
    if (!in_array($subscription['status'], $cancelable_statuses, true)) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Esta assinatura não pode ser cancelada no status atual.']];
    }

    if (!function_exists('finalize_plan_subscription')) {
      return ['code' => 'error', 'detail' => ['type' => 'toast', 'msg' => 'Recurso indisponível no momento.']];
    }

    $result = finalize_plan_subscription([
      'subscription'            => $subscription,
      'final_status'            => 'canceled',
      'reason'                  => 'admin_canceled',
      'expected_current_status' => $cancelable_statuses,
    ]);

    if (($result['code'] ?? '') !== 'success') {
      return [
        'code'   => 'error',
        'detail' => ['type' => 'toast', 'msg' => $result['msg']['message'] ?? 'Não foi possível cancelar a assinatura.'],
      ];
    }

    return ['code' => 'success', 'detail' => ['type' => 'toast', 'msg' => 'Assinatura cancelada.']];
  },
  'need_login' => true,
  'permission_callback' => '__return_true',
]);
