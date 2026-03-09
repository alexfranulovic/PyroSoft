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

async function fetchOrderAmount()
{
  const now = Date.now();

  if (amountCache !== null && (now - amountCacheAt) < 5000) {
    return amountCache;
  }

  const planId = document.querySelector('[name="plan_id"]')?.value?.trim();
  const productId = document.querySelector('[name="product_id"]')?.value?.trim();

  if (!planId && !productId) return 0;

  const params = new URLSearchParams();
  if (planId) params.append('plan_id', planId);
  else if (productId) params.append('product_id', productId);

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

function getSelectedMethod()
{
    let selector = document.querySelector('input[name="payment_method"]:checked')?.value || '';
    return selector;
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
    const method = getSelectedMethod();

    // Hide all method fields and remove required from their [data-required]
    document.querySelectorAll('[class*="-fields"]').forEach(container => {
        container.style.display = 'none';
        setRequiredWithin(container, false);
    });

    if (!method) return;

    // Show only the selected method fields and apply required to their [data-required]
    const activeContainers = document.querySelectorAll(`.${method}-fields`);
    if (!activeContainers.length) return;

    alert(hasSavedCardSelected());

    activeContainers.forEach(container => {
        container.style.display = '';
        setRequiredWithin(container, true);
    });
}

// Bind change listener
document.querySelectorAll('input[name="payment_method"]').forEach(input => {
    input.addEventListener('change', updatePaymentFields);
});


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
