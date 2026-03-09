/**
 * Requires MercadoPago.js V2:
 * <script src="https://sdk.mercadopago.com/js/v2"></script>
 */

// --- MP V2 init ---
const publicKey = document.querySelector('meta[name="mp-public-key"]').getAttribute('content');
const mp = new MercadoPago(publicKey, { locale: 'pt-BR' });

// --- Selectors (match your HTML) ---
const form = document.querySelector('form'); // ideally use a more specific selector

const cardNumberEl   = document.getElementById('payment_data[card_number]');
const nameEl         = document.getElementById('payment_data[name]');
const expirationEl   = document.getElementById('payment_data[expiration]');
const cvvEl          = document.getElementById('payment_data[cvv]');
const installmentsEl = document.getElementById('payment_data[installments]'); // may not exist
const brandHiddenEl  = document.getElementById('payment_data[card_brand]');
const methodRadios   = document.querySelectorAll('input[name="payment_method"]');
const ccFields       = document.querySelectorAll('.credit_card-fields');

// ✅ Saved cards: checkboxes (or radios) with provider_card_id
const savedCardInputs = document.querySelectorAll('[name="payment_data[provider_card_id]"]');

// Amount must come from backend
const ORDER_AMOUNT_ENDPOINT = BASE_URL + '/' + REST_API_BASE_ROUTE + '/checkout-amount';

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

// ------------------------------
// Saved card helpers
// ------------------------------
function getSelectedMethod()
{
  return document.querySelector('input[name="payment_method"]:checked')?.value || '';
}

function hasSavedCardSelected()
{
  // Works for checkbox OR radio groups; if multiple checkboxes exist, "any checked" wins.
  for (const el of savedCardInputs) {
    if (el && el.checked) return true;
  }
  return false;
}

function setChildrenRequired(container, required)
{
  // Toggle required for all inputs/selects/textareas inside cc fields
  const controls = container.querySelectorAll('input, select, textarea');
  controls.forEach(ctrl => {
    if (required) ctrl.setAttribute('required', 'true');
    else ctrl.removeAttribute('required');
  });
}

function showCcFields(show)
{
  ccFields.forEach(box => {
    box.style.display = show ? 'block' : 'none';
  });
}

function clearCcFieldValuesIfHidden()
{
  // Optional safety: don't submit old values when using another method/saved card
  // Keep brandHiddenEl & installments if you want; here we clear the visible ones only.
  if (cardNumberEl) cardNumberEl.value = '';
  if (nameEl) nameEl.value = '';
  if (expirationEl) expirationEl.value = '';
  if (cvvEl) cvvEl.value = '';

  // brand/token/installments are handled on submit; don't force-clear hidden unless you want:
  // brandHiddenEl.value = '';
}

// ------------------------------
// UI: toggle credit card fields + required/validation enabling
// Rules requested:
// - If any [name="payment_data[provider_card_id]"] is checked -> hide .credit_card-fields
// - If payment_method != credit_card -> remove required from fields inside .credit_card-fields
// - If payment_method == credit_card -> add required + enable validation,
//   BUT if saved card selected -> still hide cc fields AND remove required (since not using full card form).
// ------------------------------
function refreshPaymentFields()
{
  const selectedMethod = getSelectedMethod();
  const usingCreditCard = (selectedMethod === 'credit_card');
  const usingSavedCard = usingCreditCard && hasSavedCardSelected();

  // Payment method is NOT credit card: hide cc fields + remove required
  if (!usingCreditCard) {
    showCcFields(false);
    ccFields.forEach(box => setChildrenRequired(box, false));

    if (installmentsEl) {
      installmentsEl.removeAttribute('required');
      installmentsEl.style.display = 'none';
    }

    // Optional: clear fields to avoid stale values
    clearCcFieldValuesIfHidden();

    return;
  }

  // Payment method IS credit card:
  // If saved card selected: hide cc fields + remove required
  if (usingSavedCard) {
    showCcFields(false);
    ccFields.forEach(box => setChildrenRequired(box, false));

    if (installmentsEl) {
      // If you still want installments with saved card, keep this required=true
      // But normally you still choose installments, so keep it visible/required if it exists.
      installmentsEl.setAttribute('required', 'true');
      installmentsEl.style.display = 'block';
      if (!installmentsEl.name) installmentsEl.name = 'payment_data[installments]';
    }

    // Optional: you can keep CVV visible only if you implement a dedicated CVV field for saved card.
    // With your current setup, saved-card flow still needs a token, which requires CVV.
    // If you hide all ccFields, make sure you have a CVV-only UI elsewhere, or keep CVV inside ccFields.
    return;
  }

  // New card (credit_card selected, no saved card selected): show cc fields + required
  showCcFields(true);
  ccFields.forEach(box => setChildrenRequired(box, true));

  if (installmentsEl) {
    installmentsEl.setAttribute('required', 'true');
    installmentsEl.style.display = 'block';
    if (!installmentsEl.name) installmentsEl.name = 'payment_data[installments]';
  }
}

methodRadios.forEach(r => r.addEventListener('change', refreshPaymentFields));
savedCardInputs.forEach(el => el.addEventListener('change', refreshPaymentFields));
refreshPaymentFields();

// ------------------------------
// Card validation helpers
// ------------------------------
function digitsOnly(v) { return String(v || '').replace(/\D+/g, ''); }

function luhnCheck(num)
{
  const s = digitsOnly(num);
  if (s.length < 12) return false;

  let sum = 0;
  let alt = false;

  for (let i = s.length - 1; i >= 0; i--) {
    let n = parseInt(s.charAt(i), 10);
    if (alt) {
      n *= 2;
      if (n > 9) n -= 9;
    }
    sum += n;
    alt = !alt;
  }
  return (sum % 10) === 0;
}

function validateExpiry(mmYY)
{
  const raw = String(mmYY || '').trim();
  const parts = raw.split('/');
  if (parts.length !== 2) return { ok: false, month: null, year: null };

  const month = parseInt(parts[0].trim(), 10);
  let year = parts[1].trim();

  if (!month || month < 1 || month > 12) return { ok: false, month: null, year: null };

  if (year.length === 2) year = '20' + year;
  const yearNum = parseInt(year, 10);
  if (!yearNum || yearNum < 2000 || yearNum > 2100) return { ok: false, month: null, year: null };

  const now = new Date();
  const exp = new Date(yearNum, month, 0, 23, 59, 59);
  if (exp < now) return { ok: false, month: String(month).padStart(2, '0'), year: String(yearNum) };

  return { ok: true, month: String(month).padStart(2, '0'), year: String(yearNum) };
}

function validateCvv(v)
{
  const cvv = digitsOnly(v);
  return (cvv.length === 3 || cvv.length === 4);
}

function showCardValidationErrors(errors)
{
  if (!errors.length) return;
  alert(errors.join('\n'));
}

// ------------------------------
// Brand detection helpers
// ------------------------------
const cardBrandImgEl =
  cardNumberEl?.closest('.input-group')?.querySelector('.input-group-text img')
  || null;

let lastBin = null;

function setBrandImage(src)
{
  if (!cardBrandImgEl) return;

  if (src) {
    cardBrandImgEl.src = src;
    cardBrandImgEl.style.display = 'inline-block';
  } else {
    cardBrandImgEl.style.display = 'none';
    cardBrandImgEl.removeAttribute('src');
  }
}

async function detectCardBrandAndInstallments()
{
  // If not using new card form, do nothing
  if (getSelectedMethod() !== 'credit_card' || hasSavedCardSelected()) return;

  const cardNumber = (cardNumberEl?.value || '').replace(/\D+/g, '');
  const bin = cardNumber.length >= 8 ? cardNumber.substring(0, 8) : null;

  if (!bin) {
    lastBin = null;
    setBrandImage('');

    if (installmentsEl) {
      installmentsEl.options.length = 0;
      installmentsEl.add(new Option('Selecione', ''));
    }
    return;
  }

  if (bin === lastBin) return;
  lastBin = bin;

  try
  {
    const res = await mp.getPaymentMethods({ bin });
    const pm = res?.results?.[0];
    if (!pm?.id) return;

    if (brandHiddenEl) {
      if (!brandHiddenEl.name) brandHiddenEl.name = 'payment_data[card_brand]';
      brandHiddenEl.value = pm.id;
    }

    if (installmentsEl && !installmentsEl.name) {
      installmentsEl.name = 'payment_data[installments]';
    }

    setBrandImage(pm.secure_thumbnail || pm.thumbnail || '');

    if (installmentsEl) {
      await loadInstallments(bin);
    }
  }
  catch (e)
  {
    console.log('getPaymentMethods error:', e);
    setBrandImage('');
  }
}

['keyup', 'change', 'paste'].forEach(evt =>
  cardNumberEl?.addEventListener(evt, detectCardBrandAndInstallments)
);

// ------------------------------
// Installments (V2) - OPTIONAL
// ------------------------------
async function loadInstallments(bin)
{
  if (!installmentsEl) return;

  // If not credit card OR saved card selected, keep installments as-is (or hide)
  if (getSelectedMethod() !== 'credit_card') return;

  const cardNumber = (cardNumberEl?.value || '').replace(/\D+/g, '');
  const b = (bin || (cardNumber.length >= 8 ? cardNumber.substring(0, 8) : null));
  if (!b) return;

  if (!installmentsEl.name) installmentsEl.name = 'payment_data[installments]';

  installmentsEl.options.length = 0;
  installmentsEl.add(new Option('Carregando parcelas...', ''));

  let amountNum = 0;
  try {
    amountNum = await fetchOrderAmount();
  } catch (e) {
    console.log('fetchOrderAmount error:', e);
    installmentsEl.options.length = 0;
    installmentsEl.add(new Option('Selecione', ''));
    return;
  }

  const amountStr = Number(amountNum).toFixed(2);
  const maxNoInterest = maxNoInterestCache;

  if (!amountStr || amountStr === '0.00') {
    installmentsEl.options.length = 0;
    installmentsEl.add(new Option('Selecione', ''));
    return;
  }

  try
  {
    const res = await mp.getInstallments({
      amount: amountStr,
      bin: b,
      locale: 'pt-BR',
    });

    const payerCosts = res?.[0]?.payer_costs || [];

    const filtered = payerCosts.filter(pc => {
      const inst = Number(pc.installments || 0);
      const rate = Number(pc.installment_rate || 0);
      return inst >= 1 && inst <= maxNoInterest && rate === 0;
    });

    installmentsEl.options.length = 0;
    installmentsEl.add(new Option('Selecione', ''));

    if (!filtered.length) {
      installmentsEl.add(new Option('Sem parcelas sem juros disponíveis', ''));
      return;
    }

    filtered.forEach(pc => {
      installmentsEl.add(new Option(pc.recommended_message, String(pc.installments)));
    });
  }
  catch (e)
  {
    console.log('getInstallments error:', e);
    installmentsEl.options.length = 0;
    installmentsEl.add(new Option('Selecione', ''));
  }
}

function ensureDeviceIdHidden()
{
  const deviceId = window?.MP_DEVICE_SESSION_ID || null;
  if (!deviceId || !form) return;

  let el = form.querySelector('input[name="device_id"]');
  if (!el) {
    el = document.createElement('input');
    el.type = 'hidden';
    el.name = 'device_id';
    form.appendChild(el);
  }
  el.value = deviceId;
}

// ------------------------------
// Tokenization (V2)
// ------------------------------
async function createCardTokenFromInputs()
{
  const exp = validateExpiry(expirationEl?.value || '');
  if (!exp.ok) throw new Error('Invalid expiration date');

  const payload = {
    cardNumber: digitsOnly(cardNumberEl?.value || ''),
    cardholderName: String(nameEl?.value || '').trim(),
    cardExpirationMonth: exp.month,
    cardExpirationYear: exp.year,
    securityCode: digitsOnly(cvvEl?.value || ''),
  };

  return await mp.createCardToken(payload);
}

// ------------------------------
// Submit: validate + tokenize (universal send_form safe)
// - If payment_method != credit_card => do nothing here
// - If credit_card + saved card selected => DO NOT validate cardNumber/name/expiry,
//   but still ensure device_id and ensure installments exists.
//   NOTE: saved-card flow STILL needs token (CVV) in most gateways. If you want CVV-only,
//   implement a separate CVV field outside .credit_card-fields and tokenize with that flow.
// ------------------------------
if (form)
{
  const onClickTokenize = async function(e)
  {
    const btn = e.target.closest('[type="submit"]');
    if (!btn) return;

    const selected = getSelectedMethod();
    if (selected !== 'credit_card') return;

    const usingSavedCard = hasSavedCardSelected();

    // If already tokenized, allow universal flow
    if (form.dataset.mpTokenReady === '1') {
      delete form.dataset.mpTokenReady;
      return;
    }

    // STOP total: block universal handler on this click
    e.preventDefault();
    e.stopImmediatePropagation();

    btn.setAttribute('disabled', 'true');

    try
    {
      // For new card only: detect brand/installments from BIN
      if (!usingSavedCard) {
        await detectCardBrandAndInstallments();
      }

      const errors = [];

      if (!usingSavedCard)
      {
        const cardNumDigits = digitsOnly((cardNumberEl?.value || '').trim());
        if (!cardNumDigits || cardNumDigits.length < 12 || !luhnCheck(cardNumDigits)) {
          errors.push('Número do cartão inválido.');
        }

        const holder = String(nameEl?.value || '').trim();
        if (!holder) errors.push('Nome impresso no cartão é obrigatório.');

        const exp = validateExpiry(expirationEl?.value || '');
        if (!exp.ok) errors.push('Data de expiração inválida ou expirada.');
          // CVV is always required for tokenization in this flow
          if (!validateCvv(cvvEl?.value || '')) errors.push('CVV inválido.');

          // installments optional: if missing, force 1
          if (installmentsEl) {
            if (!String(installmentsEl.value || '').trim()) errors.push('Selecione as parcelas.');
          }
      }


      if (errors.length) {
        showCardValidationErrors(errors);
        return;
      }

      const tokenRes = await createCardTokenFromInputs();
      const tokenId = tokenRes?.id;

      if (!tokenId) {
        alert('Falha ao tokenizar o cartão. Veja o console.');
        console.log('tokenRes:', tokenRes);
        return;
      }

      // Inject token
      let tokenEl = form.querySelector('input[name="payment_data[token]"]');
      if (!tokenEl) {
        tokenEl = document.createElement('input');
        tokenEl.type = 'hidden';
        tokenEl.name = 'payment_data[token]';
        form.appendChild(tokenEl);
      }
      tokenEl.value = tokenId;

      // Ensure brand name only for NEW CARD flow
      if (!usingSavedCard && brandHiddenEl && !brandHiddenEl.name) {
        brandHiddenEl.name = 'payment_data[card_brand]';
      }

      // Ensure installments exists or default to 1
      if (installmentsEl) {
        if (!installmentsEl.name) installmentsEl.name = 'payment_data[installments]';
      } else {
        let instHidden = form.querySelector('input[name="payment_data[installments]"]');
        if (!instHidden) {
          instHidden = document.createElement('input');
          instHidden.type = 'hidden';
          instHidden.name = 'payment_data[installments]';
          form.appendChild(instHidden);
        }
        instHidden.value = '1';
      }

      // Device logic
      ensureDeviceIdHidden();

      // Mark ready and trigger universal click
      form.dataset.mpTokenReady = '1';

      form.removeEventListener('click', onClickTokenize, true);
      btn.removeAttribute('disabled');

      btn.click(); // universal sends (send_form)

      form.addEventListener('click', onClickTokenize, true);
    }
    catch (err)
    {
      console.log('Tokenize error:', err);
      alert('Verifique os dados do cartão.');
    }
    finally
    {
      btn.removeAttribute('disabled');
    }
  };

  form.addEventListener('click', onClickTokenize, true);
}
