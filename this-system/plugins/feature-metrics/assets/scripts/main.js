(function ()
{
  const BASE_URL = window.BASE_URL;
  const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;

  async function sendFeatureEvent(featureName, type) {
    if (!featureName) return;

    const body = new URLSearchParams();
    body.append("feature_name", featureName);
    body.append("type", type || "2");
    body.append("url", window.location.href);
    body.append("time", new Date().toISOString());

    try {
      await fetch(`${BASE_URL}/${REST_API_BASE_ROUTE}/feature-metric`, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "Accept": "application/json"
        },
        credentials: "same-origin",
        body: body.toString()
      });
    } catch (e) {
      console.warn("[feature-metric] Failed to send event:", e);
    }
  }

  /**
   * Update future-feature modal label if the target span exists.
   *
   * @param {string} label
   */
  function setFutureFeatureLabel(label) {
    const span = document.getElementById('future-feature-name');
    if (!span) return; // Do nothing if span does not exist
    span.textContent = label || 'This feature';
  }


  /**
   * Helper to open a Bootstrap modal by id.
   *
   * @param {string} id
   */
  function openModalById(id) {
    const modalEl = document.getElementById(id);
    if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) return;
    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  // Click / use activator
  document.addEventListener('click', function (e)
  {
    const trigger = e.target.closest('[feature-metric-name]');
    if (!trigger) return;

    const featureName     = trigger.getAttribute('feature-metric-name');
    const type            = trigger.getAttribute('feature-metric-type') || '2';
    const isClickType     = type === '2';
    const openModalFlag   = trigger.hasAttribute('feature-metric-open-modal');
    const isPremiumOnly   = trigger.hasAttribute('feature-premium-only');

    if (!featureName) return;

    e.preventDefault();
    e.stopPropagation();

    // Fire metric event
    sendFeatureEvent(featureName, type);

    // Priority 1: premium-only feature → open premium modal
    if (isPremiumOnly) {
      openModalById('premium-alert');
      return;
    }

    // Priority 2: normal click metric (type == 2) + flag to open modal → future-feature
    if (isClickType && openModalFlag) {
      // Try to update the modal label before showing it
      setFutureFeatureLabel(trigger.getAttribute('feature-metric-label') || featureName);

      openModalById('future-feature');
    }

  });

  // Optional: auto-view tracking for blocks
  document.querySelectorAll('[feature-metric-view]').forEach(function (el) {
    const featureName = el.getAttribute('feature-metric-view');
    if (!featureName) return;
    // fire and forget – view event
    sendFeatureEvent(featureName, 'view');
  });
})();
