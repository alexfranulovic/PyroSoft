const BASE_URL = window.BASE_URL;
const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;
const bootstrap = window.bootstrap;


/**
 * Collect all elements that have payment-id attribute.
 * Example: <button payment-id="123">View</button>
 */
document.addEventListener('click', async (e) => {
  const el = e.target.closest('[payment-id]');
  if (!el) return;

  const paymentId = Number(el.getAttribute('payment-id') || 0);
  if (!paymentId) return;

  el.setAttribute('disabled', 'true');

  try
  {
    const url = `${BASE_URL}/${REST_API_BASE_ROUTE}/view-payment?payment_id=${paymentId}`;

    const res = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
    });

    const json = await res.json();

    if (!json || json.code !== 'success') {
      alert('Failed to load payment.');
      return;
    }

    open_message('modal', json.payment);
  }
  catch (err) {
    alert('Network error.');
  }
  finally {
    el.removeAttribute('disabled');
  }
});
