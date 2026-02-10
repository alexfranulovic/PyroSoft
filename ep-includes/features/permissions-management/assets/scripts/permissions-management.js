/**
 * Output JavaScript for dynamically managing form fields and user interactions.
 *
 * @param $counter - An optional counter for form fields.
 */
(function ()
{
  // Reuse backend-provided globals
  const BASE_URL = window.BASE_URL;
  const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;

  /** Extracts ?id=... from an href (robust to full/relative URLs and #?id= cases). */
  function extractId(href) {
    if (!href || href === '#') return null;
    try {
      const u = new URL(href, window.location.origin);
      const id = u.searchParams.get('id');
      if (id) return id;
    } catch { /* not a full URL, fall through */ }
    const m = href.match(/[?&]id=([^&#]+)/);
    if (m) return decodeURIComponent(m[1]);
    // Fallback to legacy behavior: strip literal '?id=' and leading '#'
    return href.replace('?id=', '').replace(/^#/, '');
  }

  /** Attempts to consume non-JSON responses as HTML (optional). */
  function handleNonJSON(text) {
    if (typeof text === 'string' && text.trim().startsWith('<')) {
      // If backend returned a ready-to-open HTML fragment
      window.open_message('modal', text);
    } else {
      console.warn('[permission] Non-JSON response ignored.');
    }
  }

  // Event delegation for any element with [manage-custom-permission]
  document.addEventListener('click', async (e) => {
    const el = e.target.closest('[manage-custom-permission]');
    if (!el) return;

    e.preventDefault();

    const href = el.getAttribute('href') || '';
    const mode = el.getAttribute('manage-custom-permission') || '';
    const type = el.getAttribute('permission-type') || '';
    const id   = extractId(href);

    const url  = `${BASE_URL}/${REST_API_BASE_ROUTE}/form-manage-custom-permission`;

    // Send as application/x-www-form-urlencoded (closest to previous $.ajax default)
    const body = new URLSearchParams();
    if (id !== null) body.append('id', id);
    if (mode)        body.append('mode', mode);
    if (type)        body.append('type', type);

    try {
      const res  = await fetch(url, {
        method: 'POST',
        body,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'fetch' // hint for server if needed
        },
        credentials: 'same-origin'
      });

      const raw  = await res.text();
      let json;
      try {
        json = JSON.parse(raw);
      } catch {
        handleNonJSON(raw);
        return;
      }

      if (json?.detail?.msg) {
        window.open_message(json.detail.type || 'toast', json.detail.msg);
      }
    } catch (err) {
      console.error('[permission] Request failed:', err);
    }
  });
})();