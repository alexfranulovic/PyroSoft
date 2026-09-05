/**
 * Shared "renovação automática" switch behavior -- used by both the admin
 * "All subscriptions" listing (custom-pages/subscriptions.php) and the
 * customer "Minhas assinaturas" listing (custom-pages/my-subscriptions.php),
 * since both render the same [data-toggle-subscription-auto-renew] switch
 * (see render_subscription_auto_renew_switch_html(), index.php) and hit the
 * same toggle-subscription-auto-renew REST route either way.
 *
 * Same optimistic-update / postAction() convention as pyrosales'
 * assets/scripts/payment-methods.js.
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

  document.addEventListener('change', async function (e) {
    const el = e.target.closest('[data-toggle-subscription-auto-renew]');
    if (!el) return;

    const id          = el.dataset.toggleSubscriptionAutoRenew;
    const goingActive  = el.checked;

    el.disabled = true;

    const json = await postAction('toggle-subscription-auto-renew', { id }).catch(() => null);

    el.disabled = false;

    if (!json || json.code !== 'success') {
      el.checked = !goingActive;
      showError(json, 'Não foi possível atualizar a renovação automática.');
    }
  });

  // Admin "cancelar" button (render_subscription_admin_actions_html(), src/ui.php)
  // -- immediate cancellation via `cancel-subscription-admin` (api.php),
  // unlike the customer-facing self-service flow.
  document.addEventListener('click', async function (e) {
    const el = e.target.closest('[data-subscription-action="cancel"]');
    if (!el || el.disabled) return;

    if (!window.confirm('Cancelar esta assinatura agora? Essa ação é imediata e remove os benefícios do cliente.')) {
      return;
    }

    const subscription_id = el.dataset.subscriptionId;

    el.disabled = true;

    const json = await postAction('cancel-subscription-admin', { subscription_id }).catch(() => null);

    if (json && json.code === 'success') {
      if (json.detail && json.detail.msg && typeof open_message === 'function') {
        open_message(json.detail.type || 'toast', json.detail.msg);
      }
      window.location.reload();
      return;
    }

    el.disabled = false;
    showError(json, 'Não foi possível cancelar a assinatura.');
  });
});
