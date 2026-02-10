(function () {
  'use strict';

  const BASE_URL = window.BASE_URL;
  const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;

  /**
   * Build API URL based only on plugin/action.
   * We deliberately ignore href to avoid HTML encoding issues (&amp;).
   */
  function buildPluginUrl(plugin, action) {
    const params = new URLSearchParams();
    params.set('plugin', plugin);
    params.set('action', action);

    return `${BASE_URL}/${REST_API_BASE_ROUTE}/manage-plugins?${params.toString()}`;
  }

  /**
   * Show a message in a consistent way.
   */
  function showMessage(type, msg) {
    if (typeof window.open_message === 'function') {
      window.open_message(type, msg);
    } else {
      alert(msg);
    }
  }

  /**
   * Handle JSON response from API.
   * Expected: { code: "success"|"error", message: string, ... }
   */
  function handlePluginResponse(json) {
    if (!json) return;

    const ok   = (json.code === 'success');
    const type = ok ? 'toast' : 'modal';
    const msg  = json.message || (ok ? 'Operation completed.' : 'Operation failed.');

    showMessage(type, msg);
  }

  // Delegated click handler for elements with [plugin-action]
  document.addEventListener('click', async (e) => {
    const el = e.target.closest('[plugin-action]');
    if (!el) return;

    e.preventDefault();

    const plugin = el.getAttribute('plugin-name') || '';
    const action = el.getAttribute('plugin-action') || '';

    if (!plugin || !action) {
      console.warn('[plugins] Missing plugin-name or plugin-action attribute.', { plugin, action });
      showMessage('modal', 'Internal error: missing plugin or action.');
      return;
    }

    // Optional confirmation flow
    if (action === 'uninstall') {
      const ok = confirm(
        `Are you sure you want to uninstall the plugin "${plugin}"?\n` +
        `This may remove data associated with it.`
      );
      if (!ok) return;
    }

    const url = buildPluginUrl(plugin, action);
    console.log('[plugins] Request URL:', url);

    const originalHtml = el.innerHTML;
    const originalDisabled = el.disabled;

    // Show loading feedback only if element is not already disabled
    el.disabled = true;
    el.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';

    try {
      const res = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest' // mais padrão que "fetch"
        },
        credentials: 'same-origin'
      });

      console.log('[plugins] Response status:', res.status, res.statusText);

      const raw = await res.text();
      console.log('[plugins] Raw response:', raw);

      let json = null;

      // Tenta JSON primeiro
      try {
        json = JSON.parse(raw);
      } catch (parseErr) {
        console.warn('[plugins] Failed to parse JSON response:', parseErr);

        // Se vier HTML, tenta mostrar num modal
        const trimmed = raw.trim();
        if (trimmed.startsWith('<')) {
          showMessage('modal', trimmed);
        } else if (trimmed) {
          showMessage('modal', `Unexpected response from server:\n\n${trimmed}`);
        } else {
          showMessage('modal', 'Empty response from server.');
        }
        return;
      }

      // Temos JSON válido
      handlePluginResponse(json);

      // Se deu certo, recarrega a página para refletir estado dos plugins
      if (json.code === 'success') {
        setTimeout(() => {
          window.location.reload();
        }, 600);
      }

    } catch (err) {
      console.error('[plugins] Request failed:', err);
      showMessage('modal', 'Failed to communicate with the server.');
    } finally {
      // Restore button/link state
      el.disabled = originalDisabled;
      el.innerHTML = originalHtml;
    }
  });
})();
