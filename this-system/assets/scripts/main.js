import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

const BASE_URL = document.querySelector("meta[property='site:BASE_URL']").getAttribute("content");
window.BASE_URL = BASE_URL;

const REST_API_BASE_ROUTE = document.querySelector("meta[property='site:REST_API_BASE_ROUTE']").getAttribute("content");
window.REST_API_BASE_ROUTE = REST_API_BASE_ROUTE;


if (window.history.replaceState) window.history.replaceState(null, null, window.location.href);


// ===== Global client logger ================================================
window.logClient = async function (level, message, context = {}) {
  try {
    await fetch(BASE_URL + '/' + REST_API_BASE_ROUTE + '/log-it', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        level,
        message,
        context,
        url: location.href,
        userAgent: navigator.userAgent,
        time: new Date().toISOString()
      })
    });
  } catch (e) {
    // Do not break the app if logging fails
    console.warn('Falha ao enviar log JS:', e);
  }
};

// ===== Global error handlers ===============================================

// Runtime JS errors (syntax/throw/etc)
window.onerror = function (message, source, lineno, colno, error) {
  try {
    window.logClient('error', String(message || 'JS Error'), {
      source: source || null,
      lineno: lineno || null,
      colno: colno || null,
      stack: error && error.stack ? error.stack : null
    });
  } catch (e) {
    console.warn('Falha ao processar window.onerror:', e);
  }
};

// Unhandled Promise rejections
window.onunhandledrejection = function (event) {
  try {
    const reason = event && event.reason;
    window.logClient('error', 'Unhandled Promise Rejection', {
      reason: reason && reason.message ? reason.message : String(reason),
      stack: reason && reason.stack ? reason.stack : null
    });
  } catch (e) {
    console.warn('Falha ao processar onunhandledrejection:', e);
  }
};

// ===== Monkey patch global fetch ===========================================

(function () {
  if (!window.fetch) return; // Older browsers fallback (você já não usa, mas por segurança)

  const originalFetch = window.fetch.bind(window);

  /**
   * Monkey patch global fetch to:
   * - Log network failures (offline, DNS, etc.)
   * - Log HTTP non-OK responses (4xx / 5xx)
   *
   * Does NOT change the return type (still returns a Response).
   */
  window.fetch = async function (input, init) {
    // Try to resolve the URL in a robust way
    let url = '';
    try {
      if (typeof input === 'string') {
        url = input;
      } else if (input instanceof URL) {
        url = input.toString();
      } else if (input && typeof input === 'object' && 'url' in input) {
        url = input.url;
      }
    } catch (e) {
      // Ignore resolution failures, logging below usará empty string se necessário
    }

    try {
      const response = await originalFetch(input, init);

      // If HTTP status is not OK (4xx/5xx/etc), log but still return the response
      if (!response.ok) {
        let text = null;
        try {
          // Clone so we don't consume the body used by the rest of the app
          const clone = response.clone();
          text = await clone.text();
        } catch (e) {
          text = null;
        }

        try {
          window.logClient('error', 'Fetch returned non-OK status', {
            url: url || null,
            status: response.status,
            statusText: response.statusText,
            method: (init && init.method) || 'GET',
            responseText: text
          });
        } catch (e) {
          console.warn('Falha ao registrar erro de fetch (non-OK):', e);
        }
      }

      return response;
    } catch (err) {
      // Network-level failure or other fetch exception
      try {
        window.logClient('error', 'Fetch network failure', {
          url: url || null,
          method: (init && init.method) || 'GET',
          error: err && err.message ? err.message : String(err),
          stack: err && err.stack ? err.stack : null
        });
      } catch (e) {
        console.warn('Falha ao registrar erro de fetch (exception):', e);
      }
      // Re-throw so existing code that faz .catch() continua funcionando
      throw err;
    }
  };
})();




document.addEventListener('DOMContentLoaded', function()
{
  /*
   * Open modals with delay.
   */
  const modalTriggerList = document.querySelectorAll('[data-modal]');
  modalTriggerList.forEach(modalTrigger =>
  {
    const modalId = modalTrigger.getAttribute('id');
    let modalElement = document.getElementById(modalId);

    if (modalElement)
    {
      // Get delay attribute (default 0)
      const delayAttr = parseInt(modalElement.getAttribute('delay') || modalTrigger.getAttribute('delay') || '0', 10);
      const delay = isNaN(delayAttr) ? 0 : Math.max(0, delayAttr);

      // Create Bootstrap modal instance
      const modalInstance = new bootstrap.Modal(modalElement);

      // Show after delay
      setTimeout(() => {
        modalInstance.show();
      }, delay);

      // Clean up dynamic modals (if created_at dynamically)
      modalElement.addEventListener('hidden.bs.modal', () => {
        if (modalElement.parentElement && modalElement.parentElement.classList.contains('toast-container')) {
          modalElement.parentElement.remove();
        }
      });
    }
  });


  /*
   * Open Toasts with delay.
   */
  const toastTriggerList = document.querySelectorAll('[data-toast]');
  toastTriggerList.forEach(toastTrigger =>
  {
    const toastId = toastTrigger.getAttribute('id');
    let toastElement = document.getElementById(toastId);

    if (toastElement)
    {
      // Get delay attribute (default 0)
      const delayAttr = parseInt(toastElement.getAttribute('delay') || toastTrigger.getAttribute('delay') || '0', 10);
      const delay = isNaN(delayAttr) ? 0 : Math.max(0, delayAttr);

      // Create Bootstrap toast instance
      const toastInstance = new bootstrap.Toast(toastElement);

      // Show after delay
      setTimeout(() => {
        toastInstance.show();
      }, delay);

      // Optional cleanup for dynamically injected toasts
      toastElement.addEventListener('hidden.bs.toast', () => {
        if (toastElement.parentElement && toastElement.parentElement.classList.contains('toast-container')) {
          toastElement.parentElement.remove();
        }
      });
    }
  });


  /*
   * Open Tooltip.
   */
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(tooltipTrigger => {
    new bootstrap.Tooltip(tooltipTrigger);
  });
});


// scroll
if (document.querySelectorAll('.navbar-transparent-scroll').length > 0)
{
  var scrollWindow = function()
  {
    window.addEventListener('scroll', function()
    {
      var st = window.scrollY,
          navbar = document.querySelector('.navbar-transparent-scroll'),
          sd = document.querySelectorAll('.js-scroll-wrap');

      navbar.classList.toggle('scrolled', st > 150);

      if (st > 350) {
        navbar.classList.add('awake');
        navbar.classList.remove('sleep');
      } else {
        navbar.classList.remove('awake');
        navbar.classList.add('sleep');
      }

      sd.forEach(function(element) {
        element.classList.toggle('sleep', st > 350);
      });
    });
  };
  scrollWindow();
}



function fullHeightPage()
{
  var elements = document.querySelectorAll('[data-fullheightPage]');

  function setHeight() {
    var height = window.innerHeight + 'px';
    elements.forEach(element => element.style.height = height);
  }

  setHeight();
  window.addEventListener('resize', setHeight);
}
window.onload = fullHeightPage();




/*
 * JS for regressive counters.
 */
var all_final_moments = document.querySelectorAll("[data-final-moment]");
if (all_final_moments != null)
{
  all_final_moments.forEach(function(current_moment)
  {
    var final_moment = current_moment.getAttribute('data-final-moment');
    var slipted_moment = final_moment.split(/[\s:-]+/);
    var final_hour = new Date(slipted_moment[0], slipted_moment[1] - 1, slipted_moment[2], slipted_moment[3], slipted_moment[4], slipted_moment[5] || 0);

    var interval = setInterval(function()
    {
      var sp_now = new Date(new Date().toLocaleString('en-US', {
        timeZone: 'America/Sao_Paulo'
      }));

      var diference = final_hour - sp_now;
      var viewer_years = current_moment.querySelector('[data-years]');
      var viewer_months = current_moment.querySelector('[data-months]');
      var viewer_days = current_moment.querySelector('[data-days]');
      var viewer_hours = current_moment.querySelector('[data-hours]');
      var viewer_minutes = current_moment.querySelector('[data-minutes]');
      var viewer_seconds = current_moment.querySelector('[data-seconds]');

      if (diference <= 0)
      {
        clearInterval(interval);
        viewer_years.textContent   = '00';
        viewer_months.textContent  = '00';
        viewer_days.textContent    = '00';
        viewer_hours.textContent   = '00';
        viewer_minutes.textContent = '00';
        viewer_seconds.textContent = '00';
        return;
      }

      var years   = Math.floor(diference / (1000 * 60 * 60 * 24 * 365.25));
      var months  = Math.floor((diference % (1000 * 60 * 60 * 24 * 365.25)) / (1000 * 60 * 60 * 24 * 30.44));
      var days    = Math.floor((diference % (1000 * 60 * 60 * 24 * 30.44)) / (1000 * 60 * 60 * 24));
      var hours   = Math.floor((diference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var minutes = Math.floor((diference % (1000 * 60 * 60)) / (1000 * 60));
      var seconds = Math.floor((diference % (1000 * 60)) / 1000);

      (years !== 0) ? viewer_years.textContent = `${years}`.padStart(2, '0') : viewer_years.parentNode.style.display = 'none';
      (months !== 0) ? viewer_months.textContent = `${months}`.padStart(2, '0') : viewer_months.parentNode.style.display = 'none';
      (days !== 0) ? viewer_days.textContent = `${days}`.padStart(2, '0') : viewer_days.parentNode.style.display = 'none';
      viewer_hours.textContent = `${hours}`.padStart(2, '0');
      viewer_minutes.textContent = `${minutes}`.padStart(2, '0');
      viewer_seconds.textContent = `${seconds}`.padStart(2, '0');
    }, 500);
  });
}
