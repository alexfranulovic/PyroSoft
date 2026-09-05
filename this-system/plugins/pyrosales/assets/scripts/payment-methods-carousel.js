/**
 * A/B carousel-only variant (custom-pages/payment-methods-carousel.php):
 * whenever the carousel finishes sliding to a different card, fetch that
 * card's transactions + subscriptions in ONE request (api.php's
 * payment-method-details route, scoped server-side to the logged-in user)
 * and swap them into the two sections below the carousel -- a single round
 * trip instead of one per section. Ativar/desativar and marcar-como-padrão
 * are handled by assets/scripts/payment-methods.js, loaded alongside this
 * file -- this script only owns the "which card's details are showing"
 * concern.
 */
document.addEventListener('DOMContentLoaded', function () {
  const carouselEl = document.getElementById('payment-methods-carousel');
  const wrap       = document.querySelector('[data-payment-method-current-id]');
  if (!carouselEl || !wrap) return;

  const BASE = window.BASE_URL;
  const API  = window.REST_API_BASE_ROUTE;

  const sections = {
    transactions:  wrap.querySelector('[data-payment-method-section="transactions"] [data-payment-method-section-body]'),
    subscriptions: wrap.querySelector('[data-payment-method-section="subscriptions"] [data-payment-method-section-body]'),
  };

  function skeletonHtml(lines) {
    const rows = lines.map(w => `<div class="skeleton-line ${w}"></div>`).join('');
    return `<div class="payment-method-skeleton">${rows}</div>`;
  }

  function showSkeleton() {
    if (sections.transactions)  sections.transactions.innerHTML  = skeletonHtml(['w-75', 'w-50', 'w-75']);
    if (sections.subscriptions) sections.subscriptions.innerHTML = skeletonHtml(['w-50', 'w-75']);
  }

  function showLoadError(section) {
    if (!section) return;
    section.innerHTML = `
      <div class="payment-method-section-empty">
        <p>Não foi possível carregar essas informações agora.</p>
      </div>`;
  }

  async function fetchDetails(id) {
    const res = await fetch(`${BASE}/${API}/payment-method-details?id=${encodeURIComponent(id)}`, {
      method: 'GET',
      credentials: 'same-origin',
    });

    return res.json().catch(() => null);
  }

  let currentRequestId = 0;

  async function loadForCard(id) {
    if (!id) return;

    wrap.dataset.paymentMethodCurrentId = id;
    showSkeleton();

    // Guards against a slow earlier request landing after a newer slide
    // change -- only the latest requested card id is allowed to render.
    const requestId = ++currentRequestId;

    const details = await fetchDetails(id).catch(() => null);

    if (requestId !== currentRequestId) return; // superseded by a newer slide

    if (details && details.code === 'success') {
      if (sections.transactions)  sections.transactions.innerHTML  = details.transactions_html;
      if (sections.subscriptions) sections.subscriptions.innerHTML = details.subscriptions_html;
    } else {
      showLoadError(sections.transactions);
      showLoadError(sections.subscriptions);
    }
  }

  carouselEl.addEventListener('slid.bs.carousel', function (e) {
    const activeItem = e.relatedTarget || carouselEl.querySelector('.carousel-item.active');
    const id = activeItem ? activeItem.dataset.paymentMethodRow : null;

    if (!id || id === wrap.dataset.paymentMethodCurrentId) return;

    loadForCard(id);
  });
});
