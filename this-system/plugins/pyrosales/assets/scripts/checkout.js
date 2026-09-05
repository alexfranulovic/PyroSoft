const BASE_URL = window.BASE_URL;
const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;
const bootstrap = window.bootstrap;


const form = document.querySelector('form'); // ideally use a more specific selector
// Amount must come from backend
const ORDER_AMOUNT_ENDPOINT = BASE_URL + '/' + REST_API_BASE_ROUTE + '/checkout-amount';

const savedCardInputs = document.querySelectorAll('[name="payment_data[provider_card_id]"]');

let amountCache = null;
let amountCacheAt = 0;
let maxNoInterestCache = 1;

/**
 * Holdername uppercase.
 */
if (form)
{
    const holderEl = form.querySelector('[name="payment_data[name]"]') ?? '';
    if (holderEl)
    {
        holderEl.addEventListener('change', function ()
        {
            const start = this.selectionStart;
            const end   = this.selectionEnd;

            this.value = this.value.toUpperCase();

            // mantém o cursor no lugar (senão pula pro final)
            this.setSelectionRange(start, end);
        });
    }

    /**
     * Validate full name.
     */
    const customerNameEl = form.querySelector('[name="customer[name]"]');
    if (customerNameEl)
    {
        customerNameEl.addEventListener('blur', function ()
        {
            let value = this.value.trim().replace(/\s+/g, ' ');
            this.value = value;

            const parts = value.split(' ').filter(Boolean);

            if (parts.length < 2)
            {
                this.classList.add('is-invalid', 'is-invalid-forced');
                applyInvalidFeedback(this, 'Precisamos do seu nome completo');
            }
            else
            {
                this.classList.remove('is-invalid', 'is-invalid-forced');
            }
        });
    }
}

async function fetchOrderAmount()
{
  const now = Date.now();

  if (amountCache !== null && (now - amountCacheAt) < 5000) {
    return amountCache;
  }

  const planId = document.querySelector('[name="plan_id"]')?.value?.trim();
  const productId = document.querySelector('[name="product_id"]')?.value?.trim();
  const isOneOff = document.querySelector('[name="one_off"]')?.value?.trim();

  if (!planId && !productId && !isOneOff) return 0;

  const params = new URLSearchParams();
  if (planId) params.append('plan_id', planId);
  else if (productId) params.append('product_id', productId);
  else if (isOneOff) params.append('one_off', isOneOff);

  const endpoint = ORDER_AMOUNT_ENDPOINT + '?' + params.toString();

  const res = await fetch(endpoint, {
    method: 'GET',
    credentials: 'include',
    headers: { 'Accept': 'application/json' },
  });

  if (!res.ok) throw new Error('Failed to fetch order amount');

  const json = await res.json();

  const v = json?.amount ?? 0;
  const n = parseFloat(String(v).replace(',', '.').trim());
  amountCache = (isNaN(n) || n < 0) ? 0 : n;

  const mi = parseInt(json?.max_interest_free_installments ?? 1, 10);
  maxNoInterestCache = (!mi || mi < 1) ? 1 : Math.min(mi, 18);

  amountCacheAt = now;

  return amountCache;
}

function hasSavedCardSelected()
{
  // Works for checkbox OR radio groups; if multiple checkboxes exist, "any checked" wins.
  for (const el of savedCardInputs) {
    if (el && el.checked) return true;
  }
  return false;
}

function getSelectedMethod() {
  return document.querySelector('input[name="payment_method"]:checked');
}

function setRequiredWithin(container, isRequired)
{
    container.querySelectorAll('[data-required]').forEach(el => {
        if (isRequired) {
            el.setAttribute('required', 'required');
        } else {
            el.removeAttribute('required');
        }
    });
}

function updatePaymentFields()
{
    let method = getSelectedMethod();

    if (!method.value) return;
    method = String(method.value || '').trim();

    // Hide all method fields and remove required from their [data-required]
    document.querySelectorAll('[class*="-fields"]').forEach(container => {
        container.style.display = 'none';
        setRequiredWithin(container, false);
    });


    let isSavedPaymentMethod = false;
    let savedPaymentMethodId = null;

    // Example: "credit_card:26"
    if (method.includes(':'))
    {
        const parts = method.split(':');

        method = parts[0];
        savedPaymentMethodId = parts[1] || null;
        isSavedPaymentMethod = true;
    }

    let activeContainers = [];

    activeContainers = isSavedPaymentMethod
        ? `.${CSS.escape(method + '-fields')}.${CSS.escape(method + '-user_payment_method')}`
        : `.${CSS.escape(method + '-fields')}`;

    activeContainers = document.querySelectorAll(activeContainers);

    if (!activeContainers.length) return;

    activeContainers.forEach(container => {
        container.style.display = '';
        setRequiredWithin(container, true);
    });
}


// Bind change listener
document.querySelectorAll('input[name="payment_method"]').forEach(input => {
    input.addEventListener('click', updatePaymentFields);
});


/**
 * Check order status
 */
(function ()
{
  const el = document.querySelector('[check-order-status]');
  if (!el) return;

  const orderId = el.getAttribute('check-order-status');
  if (!orderId) return;

  const ENDPOINT = `${BASE_URL}/${REST_API_BASE_ROUTE}/check-order-status`;
  const POLL_MS = 2000;
  const MAX_ATTEMPTS = 500;

  let attempts = 0;
  let error_order = false;
  let timerId = null;

  async function poll()
  {
    if (++attempts > MAX_ATTEMPTS) return;
    if (error_order) return;

    try {
      const res = await fetch(`${ENDPOINT}?order_id=${encodeURIComponent(orderId)}`, {
        credentials: 'same-origin',
      });
      const data = await res.json();

      if (data.detail?.msg) {
        open_message(data.detail.type, data.detail.msg ?? '');
        error_order = true;
      }

      if (data.is_expired) {
        document.querySelector('[check-order-status]')?.style.setProperty('display', 'none');
        document.querySelector('.expired-payment')?.style.setProperty('display', 'block');
        error_order = true;
        return;
      }

      if (data.is_paid && data.redirect) {
        return window.location.href = data.redirect;
      }

      timerId = setTimeout(poll, POLL_MS);
    } catch (err) {
      console.error('[check-order-status]', err);
      timerId = setTimeout(poll, POLL_MS);
    }
  }

  poll();
})();


/**
 * One-off cart: quantity stepper.
 *
 * Talks exclusively to the one-off-cart API (never sends price data from
 * the client, only the new quantity) -- but paints the new total right
 * away (optimistic update) instead of waiting for the round-trip, then
 * reconciles with whatever the server actually persisted.
 */
(function ()
{
  const cartEl = document.querySelector('[data-one-off-cart]');
  if (!cartEl) return;

  const ENDPOINT = `${BASE_URL}/${REST_API_BASE_ROUTE}/one-off-cart`;

  const qtyValueEl   = cartEl.querySelector('[data-one-off-qty-value]');
  const decreaseBtn  = cartEl.querySelector('[data-one-off-qty="decrease"]');
  const increaseBtn  = cartEl.querySelector('[data-one-off-qty="increase"]');

  const totalEl     = document.querySelector('[data-checkout-total]');
  const discountRow = document.querySelector('[data-checkout-discount-row]');
  const discountEl  = document.querySelector('[data-checkout-discount-value]');
  const amountEl    = document.querySelector('[data-checkout-amount]');

  // Unit prices don't change with quantity -- read them once so the stepper
  // can recompute totals on the client immediately, without waiting on the
  // server for every click.
  const unitPrice        = Number(cartEl.dataset.unitPrice) || 0;
  const regularUnitPrice = Number(cartEl.dataset.regularUnitPrice) || 0;

  const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });

  function formatCurrency(value) {
    return currencyFormatter.format(Number(value) || 0);
  }

  function render(quantity)
  {
    const amount = unitPrice * quantity;
    const regularAmount = regularUnitPrice * quantity;
    const hasDiscount = regularAmount > 0 && amount < regularAmount;

    if (qtyValueEl) qtyValueEl.textContent = quantity;
    if (decreaseBtn) decreaseBtn.toggleAttribute('disabled', quantity <= 1);

    if (totalEl) {
      totalEl.innerHTML = hasDiscount
        ? `<s>${formatCurrency(regularAmount)}</s>`
        : `<bdi>${formatCurrency(regularAmount)}</bdi>`;
    }

    if (discountRow && discountEl) {
      if (hasDiscount) {
        const discountPercent = Math.round(((regularAmount - amount) / regularAmount) * 100);
        discountEl.textContent = `${formatCurrency(regularAmount - amount)} (${discountPercent}%)`;
        discountRow.style.display = '';
      } else {
        discountRow.style.display = 'none';
      }
    }

    if (amountEl) {
      amountEl.textContent = amount > 0 ? formatCurrency(amount) : 'GRATUITO';
    }

    // The amount shown to the payment gateway fields (installments, etc.)
    // must be recalculated on the next read.
    amountCache = null;
  }

  function currentQuantity() {
    return parseInt(qtyValueEl?.textContent || '1', 10) || 1;
  }

  // Guards against a slow request resolving after a newer click already
  // moved the quantity on -- only the latest request is allowed to paint.
  let latestRequestId = 0;

  async function updateQuantity(quantity)
  {
    const previous = currentQuantity();
    const requestId = ++latestRequestId;

    // Optimistic: show the new quantity/total right away.
    render(quantity);

    try
    {
      const res = await fetch(ENDPOINT, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_quantity', quantity }),
      });

      const json = await res.json();
      if (requestId !== latestRequestId) return; // superseded by a newer click

      if (!json || json.code !== 'success') {
        render(previous); // revert
        alert('Não foi possível atualizar a quantidade.');
        return;
      }

      // Reconcile with whatever the server actually persisted, in case it
      // differs from our optimistic guess (e.g. a server-side clamp).
      render(Number(json.item?.quantity) || quantity);
    }
    catch (err) {
      if (requestId !== latestRequestId) return;
      console.error('[one-off-cart]', err);
      render(previous); // revert
      alert('Erro de rede ao atualizar a quantidade.');
    }
  }

  if (decreaseBtn) {
    decreaseBtn.addEventListener('click', () => {
      const next = currentQuantity() - 1;
      if (next < 1) return;
      updateQuantity(next);
    });
  }

  if (increaseBtn) {
    increaseBtn.addEventListener('click', () => {
      updateQuantity(currentQuantity() + 1);
    });
  }
})();


// if (form)
// {
//   const onClickTokenize = async function(e)
//   {
//     const btn = e.target.closest('[type="submit"]');
//     if (!btn) return;

//     // STOP total: block universal handler on this click
//     e.preventDefault();
//     e.stopImmediatePropagation();

//     btn.setAttribute('disabled', 'true');

//     try
//     {

//       form.removeEventListener('click', onClickTokenize, true);
//       btn.removeAttribute('disabled');

//       btn.click(); // universal sends (send_form)

//       form.addEventListener('click', onClickTokenize, true);
//     }
//     catch (err) {
//       console.log('Tokenize error:', err);
//     }
//     finally {
//       btn.removeAttribute('disabled');
//     }
//   };

//   form.addEventListener('click', onClickTokenize, true);
// }
