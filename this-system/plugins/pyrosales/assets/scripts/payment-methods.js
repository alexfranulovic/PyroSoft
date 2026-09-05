/**
 * "Meus cartões" page behavior:
 * - toggle switch -> ativar/desativar (toggle-payment-method-status)
 * - "Marcar como padrão" button -> set-default-payment-method
 *
 * Both actions are OPTIMISTIC: the DOM is updated immediately, before the
 * network response comes back, and only rolled back on failure -- there is
 * no window.location.reload() anywhere in this file. A given card can be
 * represented TWICE on screen at once (the Individual mode carousel slide
 * AND the Tabela mode row both exist in the DOM together -- the view-mode
 * toggle just hides one of them via `.d-none`), so every helper below is
 * keyed off the card's id and queries the WHOLE document for every element
 * carrying it, rather than scoping through a single "row" container: the
 * Tabela rows come from table() (core, src/inputs), whose only per-row hook
 * is `record-id`, and relying on it here would have forced an extra hidden
 * id column just to keep header/body column counts aligned. Every
 * interactive/display element (switch, "marcar como padrão" button, default
 * badge, card visual) carries the card id directly instead.
 *
 * Deliberately NOT wrapped in <form> elements for the switch/default
 * actions -- assets/scripts/credit_card.js binds to
 * `document.querySelector("form")` (the FIRST form on the page) to know
 * where to intercept submit clicks for tokenization, so the add-card
 * <form data-payment-method-form> must stay the only <form> on this page.
 */
document.addEventListener('DOMContentLoaded', function () {
  const BASE = window.BASE_URL;
  const API  = window.REST_API_BASE_ROUTE;

  /**
   * Shared POST helper for the two lightweight mutation actions below --
   * both return the same {code, detail:{type, msg}} shape used across
   * this plugin's REST routes (see api.php).
   */
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

  /** Every element carrying a given card id, regardless of which view mode renders it. */
  const switchesFor       = id => document.querySelectorAll(`[data-toggle-payment-method="${id}"]`);
  const defaultBadgesFor  = id => document.querySelectorAll(`[data-default-badge="${id}"]`);
  const defaultButtonsFor = id => document.querySelectorAll(`[data-set-default-payment-method="${id}"]`);
  // The realistic card visual only exists in Individual mode (rendered inside
  // its carousel item, which still carries data-payment-method-row) -- these
  // simply come back empty while Tabela mode is what's showing, which is fine,
  // every call site below already tolerates an empty NodeList.
  const visualsFor       = id => document.querySelectorAll(`[data-payment-method-row="${id}"] .payment-card-visual`);
  const visualBadgesFor  = id => document.querySelectorAll(`[data-payment-method-row="${id}"] .payment-card-visual-badge`);

  function isDefaultNow(id) {
    return Array.from(defaultBadgesFor(id)).some(badge => badge.style.display !== 'none');
  }

  function setDefaultUi(id, isDefault) {
    defaultBadgesFor(id).forEach(badge => { badge.style.display = isDefault ? '' : 'none'; });
    defaultButtonsFor(id).forEach(button => { button.style.display = isDefault ? 'none' : ''; });
    updateVisualStatusBadge(id);
  }

  /**
   * "Marcar como padrão" button, active/inactive dimension: while a card
   * is NOT default, the button stays visible (setDefaultUi() above is what
   * hides it entirely once the card IS default -- that's a different,
   * unrelated reason to not show it) but gets `disabled` while the card is
   * inactive, so the option is still legible on screen instead of just
   * disappearing.
   */
  function setActiveUi(id, isActive) {
    if (!isDefaultNow(id)) {
      defaultButtonsFor(id).forEach(button => { button.disabled = !isActive; });
    }

    // The credit-card visual itself (Individual mode): grayscale treatment.
    visualsFor(id).forEach(visual => visual.classList.toggle('payment-card-visual-inactive', !isActive));

    updateVisualStatusBadge(id);
  }

  /**
   * Keeps .payment-card-visual-badge (rendered by render_credit_card_visual_html(),
   * src/ui.php -- always present, never an empty element, Individual mode
   * only) in sync with the card's current default/active state: "Padrão"
   * wins over "Ativo"/"Inativo" since a default card is always active by
   * business rule (see update_payment_method_status(), src/payment_methods.php).
   */
  function updateVisualStatusBadge(id) {
    const badges = visualBadgesFor(id);
    if (!badges.length) return;

    const isDefault = isDefaultNow(id);
    const toggle = switchesFor(id)[0];
    const isActive = toggle ? toggle.checked : true;

    badges.forEach(badge => {
      if (isDefault) {
        badge.textContent = 'Padrão';
        badge.className = 'badge text-bg-light payment-card-visual-badge';
      } else if (isActive) {
        badge.textContent = 'Ativo';
        badge.className = 'badge text-bg-success payment-card-visual-badge';
      } else {
        badge.textContent = 'Inativo';
        badge.className = 'badge text-bg-danger payment-card-visual-badge';
      }
    });
  }

  // ---- Ativar / desativar --------------------------------------------
  document.addEventListener('change', async function (e) {
    const el = e.target.closest('[data-toggle-payment-method]');
    if (!el) return;

    const id = el.dataset.togglePaymentMethod;
    const goingActive = el.checked;

    // Optimistic: reflect the new state everywhere immediately.
    switchesFor(id).forEach(sw => { sw.checked = goingActive; sw.disabled = true; });
    setActiveUi(id, goingActive);
    // Deactivating also clears "padrão" server-side -- mirror that here.
    if (!goingActive) setDefaultUi(id, false);

    const json = await postAction('toggle-payment-method-status', { id }).catch(() => null);

    switchesFor(id).forEach(sw => { sw.disabled = false; });

    if (!json || json.code !== 'success') {
      // Revert everything optimistically applied above.
      switchesFor(id).forEach(sw => { sw.checked = !goingActive; });
      setActiveUi(id, !goingActive);
      showError(json, 'Não foi possível atualizar o status do cartão.');
    }
  });

  // ---- Marcar como padrão --------------------------------------------
  document.addEventListener('click', async function (e) {
    const el = e.target.closest('[data-set-default-payment-method]');
    if (!el) return;

    const id = el.dataset.setDefaultPaymentMethod;

    // Snapshot which card WAS default, so a failure can be reverted precisely.
    let previousDefaultId = null;
    const allDefaultBadges = document.querySelectorAll('[data-default-badge]');
    allDefaultBadges.forEach(badge => {
      if (badge.style.display !== 'none') previousDefaultId = badge.dataset.defaultBadge;
    });

    el.disabled = true;

    // Optimistic: clear "padrão" from every card, then set it on the target
    // -- only one card can be default at a time.
    const allIds = new Set();
    allDefaultBadges.forEach(badge => allIds.add(badge.dataset.defaultBadge));
    allIds.forEach(cardId => setDefaultUi(cardId, false));
    setDefaultUi(id, true);

    const json = await postAction('set-default-payment-method', { id }).catch(() => null);

    el.disabled = false;

    if (!json || json.code !== 'success') {
      // Revert: clear the optimistic default, restore the previous one.
      setDefaultUi(id, false);
      if (previousDefaultId) {
        setDefaultUi(previousDefaultId, true);
      }
      showError(json, 'Não foi possível marcar este cartão como padrão.');
    }
  });

  // ---- Individual / Tabela view-mode toggle ----------------------------
  // Both `[data-view-mode]` blocks stay in the DOM at all times (Individual
  // is the one carrying the carrossel + pedidos/assinaturas markup, Tabela
  // the compact list) -- clicking a toggle button just swaps which one is
  // hidden via `.d-none`, and mirrors the active/outline button classes the
  // same way Bootstrap's own `btn-group` "pressed" state looks.
  const viewToggle = document.querySelector('[data-view-toggle]');
  if (viewToggle) {
    const toggleButtons = viewToggle.querySelectorAll('[data-view-toggle-btn]');
    const viewSections  = document.querySelectorAll('[data-view-mode]');

    toggleButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        const mode = btn.dataset.viewToggleBtn;

        toggleButtons.forEach(function (b) {
          const active = b === btn;
          b.classList.toggle('btn-primary', active);
          b.classList.toggle('btn-outline-primary', !active);
          b.classList.toggle('active', active);
          if (active) b.setAttribute('aria-current', 'page');
          else b.removeAttribute('aria-current');
        });

        viewSections.forEach(function (section) {
          section.classList.toggle('d-none', section.dataset.viewMode !== mode);
        });
      });
    });
  }

  // ---- Carousel: eager init (touch fix) + "3/6" counter ---------------
  const carouselEl = document.getElementById('payment-methods-carousel');
  if (carouselEl && window.bootstrap && window.bootstrap.Carousel) {
    /**
     * Bootstrap only wires up its swipe/touch listeners inside the
     * Carousel constructor (Carousel._init() -> _addTouchEventListeners()),
     * and that constructor only ever ran, until now, the first time
     * something clicked a [data-bs-slide]/[data-bs-slide-to] control --
     * that's Bootstrap's own click-delegation lazily creating the
     * instance on demand. With no `data-bs-ride="carousel"` attribute
     * (deliberately absent -- this carousel must never auto-play), nothing
     * else ever created it earlier, so a swipe attempted before that first
     * button click landed on a carousel with no JS instance at all and did
     * nothing. Creating the instance eagerly here (still with no autoplay,
     * since `ride` defaults to false without a `data-bs-ride` attribute)
     * makes touch work from the very first swipe.
     */
    const carousel = window.bootstrap.Carousel.getOrCreateInstance(carouselEl);

    const counterWrap    = carouselEl.querySelector('[data-carousel-counter]');
    const counterCurrent = counterWrap ? counterWrap.querySelector('[data-carousel-counter-current]') : null;

    if (counterCurrent) {
      carouselEl.addEventListener('slid.bs.carousel', function (e) {
        counterCurrent.textContent = e.to + 1;
      });
    }
  }
});
