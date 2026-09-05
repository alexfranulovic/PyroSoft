/**
 * "Minhas assinaturas" page behavior:
 * - statement_descriptor input -> saves on blur, posting to the merged
 *   update-subscription-field route (api.php).
 *
 * The payment-method column used to have its own dropdown + click handler
 * here, but that dropdown (render_subscription_payment_method_dropdown_html(),
 * plans-subscriptions/src/ui.php) was replaced by pyrosales' own
 * `user_payment_methods` input (plugins/pyrosales/inputs/user_payment_methods)
 * -- its behavior (including the "save on pick" auto-save this listing
 * relies on, via `data-subscription-id`) now lives in that input's own
 * dedicated script, pyrosales/assets/scripts/user-payment-methods-input.js.
 *
 * Same optimistic-update / postAction() convention as pyrosales'
 * assets/scripts/payment-methods.js. auto_renew's switch is handled by the
 * shared assets/scripts/subscriptions.js, enqueued alongside this file.
 */
document.addEventListener('DOMContentLoaded', function () {
  const BASE = window.BASE_URL;
  const API  = window.REST_API_BASE_ROUTE;

  async function postAction(action, body) {
    const res = await fetch(`${BASE}/${API}/${action}`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });

    return res.json().catch(() => null);
  }

  function showError(json, fallback) {
    if (json && json.detail && json.detail.msg && typeof open_message === 'function') {
      open_message(json.detail.type || 'toast', json.detail.msg);
    } else {
      alert(fallback);
    }
  }

  // ---- statement_descriptor: save on blur -------------------------------
  document.addEventListener('blur', async function (e) {
    const el = e.target.closest('[data-subscription-statement-descriptor]');
    if (!el) return;

    const id           = el.dataset.subscriptionStatementDescriptor;
    const value         = el.value;
    const previousValue = el.defaultValue;

    if (value === previousValue) return;

    el.disabled = true;

    const json = await postAction('update-subscription-field', {
      id,
      statement_descriptor: value,
    }).catch(() => null);

    el.disabled = false;

    if (json?.detail?.msg) open_message(json.detail.type, json.detail.msg);

    if (!json || json.code !== 'success') {
      el.value = previousValue;
      showError(json, 'Não foi possível salvar o statement descriptor.');
    } else {
      el.defaultValue = value;
    }
  }, true); // capture -- 'blur' doesn't bubble
});
