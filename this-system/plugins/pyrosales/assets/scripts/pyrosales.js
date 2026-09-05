const BASE_URL = window.BASE_URL;
const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;
const bootstrap = window.bootstrap;


/**
 *
 * Collect all elements that have payment-id attribute.
 * Example: <button payment-id="123">View</button>
 *
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

/**
 *
 * Toggle the amount field (visibility + required) based on full/partial selection.
 *
 */
document.addEventListener('change', (e) => {
  const radio = e.target.closest('[data-cancel-payment-id] [name="refund-mode"]');
  if (!radio) return;

  toggleAmountField(radio);
});

function toggleAmountField(radio) {
  const wrapper = radio.closest('[data-cancel-payment-id]');
  const amountInput = wrapper.querySelector('[name="to_refund_amount"]');
  if (!amountInput) return;

  const amountFieldWrapper = amountInput.closest('[style]') || amountInput.parentElement;
  const isPartial = radio.value === 'partial';

  amountFieldWrapper.style.display = isPartial ? '' : 'none';

  if (isPartial) {
    amountInput.setAttribute('required', 'required');
  } else {
    amountInput.removeAttribute('required');
    amountInput.value = ''; // clear stale value when switching back to full
  }
}

// Enforce initial state (in case "full" isn't the actual default in some edge case,
// or the collapse is expanded programmatically without a prior change event)
document.querySelectorAll('[data-cancel-payment-id] [name="refund-mode"]:checked').forEach(toggleAmountField);


/**
 *
 * Order notes: display filters (switches in the offcanvas header dropdown).
 * Each switch carries data-note-filter="<selector>" and toggles matching
 * elements inside the notes list. Runs before the offcanvas is ever opened,
 * so there's no flash of hidden content.
 *
 */
const noteFilterSwitches = document.querySelectorAll('#notes [data-note-filter]');

// Scrolls the notes list to the bottom so the most recent note is visible.
function scrollNotesToBottom()
{
  const list = document.querySelector('#notes .list-notes');
  if (!list) return;

  list.scrollTop = list.scrollHeight;
}

// A filter's data-note-filter value can be a comma-separated selector list
// (e.g. ".to-customer, .from-customer" so one switch covers both). Each part
// needs the "#notes .list-notes" scope applied individually, otherwise the
// comma would break out of the scope for every part after the first.
function scopedNoteSelector(selector)
{
  return selector
    .split(',')
    .map((part) => `#notes .list-notes ${part.trim()}`)
    .join(', ');
}

function applyNoteFilter(sw, { scroll = false } = {})
{
  const selector = sw.getAttribute('data-note-filter');
  if (!selector) return;

  document.querySelectorAll(scopedNoteSelector(selector)).forEach((el) => {
    el.style.display = sw.checked ? '' : 'none';
  });

  // Revealing a note type can bring new content into view further down the list.
  if (scroll && sw.checked) scrollNotesToBottom();
}

// Applies the current switch states to a single freshly-added note element.
function applyNoteFiltersTo(el)
{
  noteFilterSwitches.forEach((sw) => {
    const selector = sw.getAttribute('data-note-filter');
    if (selector && el.matches(selector) && !sw.checked) {
      el.style.display = 'none';
    }
  });
}

noteFilterSwitches.forEach((sw) => {
  applyNoteFilter(sw); // initial state, offcanvas isn't open yet — no need to scroll
  sw.addEventListener('change', () => applyNoteFilter(sw, { scroll: true }));
});

// Start scrolled to the most recent note, same as after sending one.
scrollNotesToBottom();

/**
 * Order notes: send a note as private or to the customer.
 */
(function ()
{
  const form = document.querySelector('[data-add-note-form]');
  if (!form) return;

  const textarea  = form.querySelector('[name="content"]');
  const submitBtn = form.querySelector('[type="submit"]');
  const orderId   = form.getAttribute('data-order-id');

  const listWrap   = document.querySelector('#notes .offcanvas-body.list-notes');
  const emptyState = document.querySelector('#notes .offcanvas-body.empty-state');

  const ENDPOINT = `${BASE_URL}/${REST_API_BASE_ROUTE}/order-note-manager`;

  function toggleSubmit()
  {
    if (!submitBtn) return;
    const hasContent = !!(textarea && textarea.value.trim());
    submitBtn.toggleAttribute('disabled', !hasContent);
  }

  if (textarea) {
    textarea.addEventListener('input', toggleSubmit);
    toggleSubmit();
  }

  function getDialog()
  {
    let dialog = document.querySelector('#notes .list-notes .dialog');

    if (!dialog && listWrap) {
      dialog = document.createElement('div');
      dialog.className = 'dialog';
      listWrap.appendChild(dialog);
    }

    return dialog;
  }

  function buildNoteEl(note)
  {
    const isCustomer = note.visibility === 'customer';

    let visibilityClass = 'private';
    let faIcon = 'fa-note-sticky';

    if (note.from_customer) {
      visibilityClass = 'from-customer';
      faIcon = 'fa-user';
    } else if (isCustomer) {
      visibilityClass = 'to-customer';
      faIcon = 'fa-message';
    }

    let time = '';
    if (note.created_at) {
      const parsed = new Date(note.created_at.replace(' ', 'T'));
      time = isNaN(parsed) ? '' : parsed.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    const article = document.createElement('article');
    article.className = `${visibilityClass} body-type-message`;

    const author = document.createElement('span');
    author.className = 'created-by';
    author.title = `Note by ${note.author || 'System'}`;
    author.innerHTML = `<i class="fas ${faIcon}"></i> `;
    author.append(note.author || 'System');

    const content = document.createElement('p');
    content.className = 'content';
    content.textContent = note.content || '';

    const timeEl = document.createElement('p');
    timeEl.className = 'time';
    timeEl.title = `Sent at ${time}`;
    timeEl.textContent = time;

    article.append(author, content, timeEl);

    applyNoteFiltersTo(article);

    return article;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const content = textarea ? textarea.value.trim() : '';
    if (!content || !orderId) return;

    const visibility = form.querySelector('[name="visibility"]:checked')?.value || 'private';

    if (submitBtn) submitBtn.setAttribute('disabled', 'true');

    try
    {
      const res = await fetch(ENDPOINT, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, content, visibility }),
      });

      const json = await res.json();

      if (!json || json.code !== 'success') {
        if (json?.detail?.msg) {
          open_message(json.detail.type, json.detail.msg);
        } else {
          alert('Failed to save note.');
        }
        return;
      }

      if (emptyState) emptyState.style.display = 'none';
      if (listWrap) listWrap.style.display = '';

      const dialog = getDialog();
      if (dialog) {
        dialog.appendChild(buildNoteEl(json.note));
        scrollNotesToBottom();
      }

      textarea.value = '';
    }
    catch (err) {
      console.error('[order-note-manager]', err);
      alert('Network error.');
    }
    finally {
      toggleSubmit();
    }
  });
})();
