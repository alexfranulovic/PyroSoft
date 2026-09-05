/**
 * Dedicated behavior for the `user_payment_methods` input
 * (plugins/pyrosales/inputs/user_payment_methods) -- enqueued by
 * input_user_payment_methods() itself (add_asset('footer')), so it's only
 * ever loaded on a page that actually renders this input.
 *
 * Picking a `[data-payment-method-option]` updates:
 * - the toggle button's label ([data-payment-method-toggle]), and
 * - the hidden input carrying the real value ([data-payment-method-hidden],
 *   '' meaning "system default" / no card picked),
 * so the field behaves like any other form input -- whatever <form> wraps
 * it (e.g. the "Atualizar pagamento" modal, plans-subscriptions'
 * custom-pages/my-subscriptions.php) submits the hidden value on its own.
 *
 * `data-subscription-id` on the `[data-payment-method-dropdown]` wrapper is
 * OPTIONAL (passed through the input's own `attributes` option) -- when
 * present, a pick ALSO auto-saves immediately via update-subscription-field
 * (plans-subscriptions/api.php), for a caller with no wrapping <form> to
 * submit (see plans-subscriptions' custom-listings/my-subscriptions.php).
 * Without it, this is a no-op beyond the DOM sync above.
 */
document.addEventListener('click', async function (e)
{
  const option = e.target.closest('[data-payment-method-option]');
  if (!option) return;
  e.preventDefault();

  const dropdown = option.closest('[data-payment-method-dropdown]');
  if (!dropdown) return;

  const toggle        = dropdown.querySelector('[data-payment-method-toggle]');
  const hidden        = dropdown.querySelector('[data-payment-method-hidden]');
  const previousActive = dropdown.querySelector('.dropdown-item.active');

  const previousToggleHtml = toggle ? toggle.innerHTML : '';
  const previousValue      = hidden ? hidden.value : '';
  const value               = option.dataset.paymentMethodOption || '';

  // Sync the visible field -- always, regardless of data-subscription-id.
  if (toggle) toggle.innerHTML = option.innerHTML;
  if (hidden) hidden.value = value;
  if (previousActive) previousActive.classList.remove('active');
  option.classList.add('active');
  if (hidden) hidden.dispatchEvent(new Event('change', { bubbles: true }));

  const subscriptionId = dropdown.dataset.subscriptionId;
  if (!subscriptionId) return; // Plain input -- the wrapping <form> submits the hidden value.

  // Auto-save path: no wrapping <form> to submit (e.g. an inline-editable
  // listing cell) -- optimistic, reverts on failure.
  try
  {
    const res = await fetch(`${window.BASE_URL}/${window.REST_API_BASE_ROUTE}/update-subscription-field`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: subscriptionId, user_payment_method_id: value }),
    });

    const json = await res.json().catch(() => null);

    if (json?.detail?.msg && typeof open_message === 'function') {
      open_message(json.detail.type, json.detail.msg);
    }

    if (!json || json.code !== 'success') {
      throw new Error('Could not save the payment method.');
    }
  }
  catch (err)
  {
    if (toggle) toggle.innerHTML = previousToggleHtml;
    if (hidden) hidden.value = previousValue;
    option.classList.remove('active');
    if (previousActive) previousActive.classList.add('active');

    if (typeof open_message !== 'function') {
      alert('Não foi possível atualizar o meio de pagamento.');
    }
  }
});
