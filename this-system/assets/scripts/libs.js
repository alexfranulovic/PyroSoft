const BASE_URL = window.BASE_URL;
const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;
const bootstrap = window.bootstrap;

/*ScrollReveal*/
import ScrollReveal from 'scrollreveal';

/*DataTables*/
import DataTable from 'datatables.net-bs5';
import pt_BR from './inc/datatables-pt-br.js';

/*TomSelect*/
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

/*IMask*/
import IMask from 'imask';

/*Fontawesome*/
import '@fortawesome/fontawesome-free/css/all.min.css';
import '../styles/main.scss';


window.addEventListener('load', function ()
{
  /**
   * Open & close the bulk edit section of List pages (CRUD's)
   */
  const quickEditButtons = document.querySelectorAll('[aria-controls="bulk-edit"]');
  quickEditButtons.forEach(button => {
    button.addEventListener('click', event => {
      event.preventDefault();

      const closestCollapse = button.closest('.card').querySelector('.collapse');
      if (closestCollapse) new bootstrap.Collapse(closestCollapse).show();
    });
  });

});


/**
 *
 * DOM Content Loaded
 *
 */
document.addEventListener('DOMContentLoaded', function()
{
  /**
   *
   * ScrollReveal
   *
   */
  window.sr = ScrollReveal({reset: false});
  function revealAnimation(selector, options) {
    const elements = document.querySelectorAll(selector);
    if (elements.length > 0) sr.reveal(selector, options);
  }
  revealAnimation('.animate-top', { duration: 1000, origin: 'top', distance: '0.625' });
  revealAnimation('.animate-left', { duration: 1000, origin: 'left', distance: '0.625' });
  revealAnimation('.animate-center', { duration: 1000, origin: 'center', distance: '0.625' });
  revealAnimation('.animate-right', { duration: 1000, origin: 'right', distance: '0.625' });
  revealAnimation('.animate-bottom', { duration: 1000, origin: 'bottom', distance: '0.625' });
  revealAnimation('.animate-reverse-top', { duration: 1000, origin: 'top', distance: '0.625', reset: true });
  revealAnimation('.animate-reverse-left', { duration: 1000, origin: 'left', distance: '0.625', reset: true });
  revealAnimation('.animate-reverse-center', { duration: 1000, origin: 'center', distance: '0.625', reset: true });
  revealAnimation('.animate-reverse-right', { duration: 1000, origin: 'right', distance: '0.625', reset: true });
  revealAnimation('.animate-reverse-bottom', { duration: 1000, origin: 'bottom', distance: '0.625', reset: true });


  /**
   *
   * Open CRUD piece as modal.
   *
   */
  document.addEventListener('click', async (e) =>
  {
    const trigger = e.target.closest('[open-crud-piece]');
    if (!trigger) return; // not our element

    e.preventDefault(); // block default action

    const crudPiece = trigger.getAttribute('open-crud-piece');
    const itemId    = trigger.getAttribute('item-id') || null; // optional

    if (!crudPiece) {
      console.error("Missing attribute [open-crud-piece]");
      return;
    }

    // Show loader
    const loader = document.createElement('div');
    loader.className = 'crud-loader';
    loader.innerHTML = '<p>Loading...</p>';
    document.body.appendChild(loader);

    try {
      const form = new FormData();
      form.append('piece_id', crudPiece);
      if (itemId) form.append('register_id', itemId);

      const res = await fetch(`${BASE_URL}/${REST_API_BASE_ROUTE}/get-crud-piece`, {
        method: 'POST',
        body: form,
      });

      if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
      const data = await res.json();

      // Abre via open_message (espera .modal no HTML de msg)
      if (data && data.detail && data.detail.msg) {
        const kind = (data.detail.type === 'modal') ? 'modal' : 'toast';
        open_message(kind, data.detail.msg);
      }

    } catch (err) {
      console.error('Error fetching CRUD piece:', err);
    } finally {
      loader.remove();
    }

  });


  /**
   *
   * Datatables
   *
   **/
  // ----- Client-side tables -----
  document.querySelectorAll('.data-table, [data-table]').forEach(function (el)
  {
    // eslint-disable-next-line no-new
    new DataTable(el, {
      language: pt_BR
    });
  });

  // Helper: serialize DataTables request object (nested) into FormData (bracket notation)
  function appendFormData(fd, data, parentKey)
  {
    if (Array.isArray(data)) {
      for (var i = 0; i < data.length; i++) {
        appendFormData(fd, data[i], parentKey ? parentKey + '[' + i + ']' : String(i));
      }
    } else if (data !== null && typeof data === 'object') {
      for (var k in data) {
        if (!Object.prototype.hasOwnProperty.call(data, k)) continue;
        var v = data[k];
        var key = parentKey ? parentKey + '[' + k + ']' : k;
        appendFormData(fd, v, key);
      }
    } else if (parentKey) {
      fd.append(parentKey, data == null ? '' : data);
    }
  }

  // ----- Server-side tables -----
  document.querySelectorAll('.data-table-async, [data-table-async]').forEach(function (el)
  {
    var crudId = el.getAttribute('data-crud-id') || '';
    var loader = el.closest('[data-table-loader]');

    // eslint-disable-next-line no-new
    new DataTable(el,
    {
      language: pt_BR,
      processing: true,
      serverSide: true,
      ajax: function (dtRequest, callback /*, settings */)
      {
        // show loader
        if (loader) loader.style.removeProperty('display');

        var body = new FormData();
        appendFormData(body, dtRequest, '');     // draw, start, length, search, order, columns...
        if (crudId) body.append('crud_id', crudId);

        fetch(BASE_URL + '/' + REST_API_BASE_ROUTE + '/get-crud-list', {
          method: 'POST',
          body: body
        })

          .then(function (res) { return res.json(); })
          .then(function (json) {
            // Expected: { draw, recordsTotal, recordsFiltered, data, ... }
            callback(json);
          })

          .catch(function (err) {
            console.error('DataTable ajax error:', err);
            callback({ draw: dtRequest.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
          })

          .finally(function () {
            // hide loader
            if (loader) loader.style.display = 'none';
          });
      }
    });
  });

});


/**
 * IMask + Money masks for dynamic content
 * - Works for elements present at load and those added later
 */
(function () {
  'use strict';

  // ─────────────────────────────────────────────────────────────
  // Masks (IMask)
  // ─────────────────────────────────────────────────────────────
  var MASK_PATTERNS = [
    ['.mask-height',             { mask: '0.00' }],
    ['.mask-rg',                 { mask: '00.000.000-0' }],
    ['.mask-cpf',                { mask: '000.000.000-00' }],
    ['.mask-cnpj',               { mask: '00.000.000/0000-00' }],
    ['.mask-cep',                { mask: '00000-000' }],

    // ✅ FIX: dynamic phone mask (IMask needs dispatch)
    ['.mask-phone', {
      mask: [
        { mask: '(00) 0000-0000' },   // 10 digits
        { mask: '(00) 00000-0000' }   // 11 digits
      ],
      dispatch: function (appended, dynamicMasked) {
        var number = (dynamicMasked.value + appended).replace(/\D/g, '');
        return number.length > 10
          ? dynamicMasked.compiledMasks[1]
          : dynamicMasked.compiledMasks[0];
      }
    }],

    ['.mask-credit-card-number', { mask: '0000 0000 0000 0000' }],
    ['.mask-credit-card-date',   { mask: '00/00' }],
    ['.mask-credit-card-cvv',    { mask: '0000' }]
  ];

  function maskable(el) {
    return !!(el && el.nodeType === 1 && !el.disabled && !el.readOnly);
  }

  function collect(root, selector) {
    var out = [];
    if (!root || (root.nodeType !== 1 && root !== document)) return out;

    if (root.matches && root.matches(selector)) out.push(root);

    if (root.querySelectorAll) {
      var inner = root.querySelectorAll(selector);
      for (var i = 0; i < inner.length; i++) out.push(inner[i]);
    }
    return out;
  }

  function initIMask(el, opts) {
    if (!maskable(el)) return;
    if (el.dataset.imaskInit === '1') return;
    if (typeof IMask !== 'function') return;

    IMask(el, opts);
    el.dataset.imaskInit = '1';
  }

  function scanIMasks(root) {
    for (var i = 0; i < MASK_PATTERNS.length; i++) {
      var sel = MASK_PATTERNS[i][0];
      var opt = MASK_PATTERNS[i][1];
      var list = collect(root, sel);

      for (var j = 0; j < list.length; j++) {
        initIMask(list[j], opt);
      }
    }
  }

  // Lazy init on focus (handles dynamic inputs)
  document.addEventListener('focusin', function (e) {
    var t = e.target;
    if (!t || !t.matches) return;

    for (var i = 0; i < MASK_PATTERNS.length; i++) {
      if (t.matches(MASK_PATTERNS[i][0])) {
        initIMask(t, MASK_PATTERNS[i][1]);
        break;
      }
    }
  });

  // ─────────────────────────────────────────────────────────────
  // Money mask (custom)
  // ─────────────────────────────────────────────────────────────
  function formatMoney(raw) {
    raw = (raw == null ? '' : String(raw)).trim();
    // keep only digits; last 2 digits are cents
    var digits = raw.replace(/\D/g, '');
    var cents = digits.slice(-2).padStart(2, '0');
    var ints  = digits.slice(0, -2) || '0';

    // add thousand separators
    ints = ints.replace(/^0+(?=\d)/, '');
    ints = ints.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return ints + ',' + cents;
  }

  function formatMoneyInput(el) {
    if (!maskable(el)) return;

    var before = el.value || '';
    var start = el.selectionStart || 0;
    var digitsBefore = before.slice(0, start).replace(/\D/g, '').length;

    var after = formatMoney(before);
    if (before === after) return;

    el.value = after;

    // restore cursor roughly based on digit count before cursor
    try {
      var pos = 0, seen = 0;
      while (pos < after.length) {
        if (/\d/.test(after.charAt(pos))) seen++;
        if (seen >= digitsBefore) break;
        pos++;
      }
      el.setSelectionRange(pos + 1, pos + 1);
    } catch (e) {}
  }

  function scanMoney(root) {
    var list = collect(root, '.mask-money').concat(collect(root, '[mask-money]'));
    for (var i = 0; i < list.length; i++) {
      if (list[i].dataset.moneyInit !== '1') {
        formatMoneyInput(list[i]);
        list[i].dataset.moneyInit = '1';
      }
    }
  }

  document.addEventListener('input', function (e) {
    var el = e.target;
    if (!el || !el.matches) return;
    if (el.matches('.mask-money, [mask-money]')) formatMoneyInput(el);
  });

  document.addEventListener('blur', function (e) {
    var el = e.target;
    if (!el || !el.matches) return;
    if (el.matches('.mask-money, [mask-money]')) formatMoneyInput(el);
  }, true);

  // ─────────────────────────────────────────────────────────────
  // MutationObserver (dynamic nodes)
  // ─────────────────────────────────────────────────────────────
  var mo = new MutationObserver(function (mutations) {
    for (var m = 0; m < mutations.length; m++) {
      var added = mutations[m].addedNodes;
      for (var k = 0; k < added.length; k++) {
        var node = added[k];
        if (!node || node.nodeType !== 1) continue;
        scanIMasks(node);
        scanMoney(node);
      }
    }
  });
  mo.observe(document.documentElement || document.body, { childList: true, subtree: true });

  // Bootstrap modal hook
  document.addEventListener('shown.bs.modal', function (e) {
    var modal = e.target;
    if (!modal) return;
    scanIMasks(modal);
    scanMoney(modal);
  });

  // Public manual apply
  window.applyMasks = function (root) {
    root = root || document;
    scanIMasks(root);
    scanMoney(root);
  };

  document.addEventListener('DOMContentLoaded', function () {
    window.applyMasks(document);
  });

  // ─────────────────────────────────────────────────────────────
  // Slugify
  // ─────────────────────────────────────────────────────────────
  function slugify(str) {
    return (str || '')
      .replace(/ /g, '-')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[<,>.?/:;"'\{\[\}\]\|\\~`!@#\$%\^&\*\(\)_\+=]+/g, '')
      .replace(/\-\-+/g, '-')
      .replace(/(^-+|-+$)/g, '')
      .toLowerCase();
  }

  // cache target list on demand (avoid querying on every key)
  var slugTargets = null;

  document.addEventListener('keyup', function (e) {
    var t = e.target;
    if (!t || !t.closest) return;
    if (!t.closest('.mask-name')) return;

    if (!slugTargets) slugTargets = document.querySelectorAll('.mask-username');

    var val = slugify(t.value || '');
    for (var i = 0; i < slugTargets.length; i++) {
      slugTargets[i].value = val;
    }
  });
})();




/**
 *
 * Cookies
 *
 */
function setCookie(name, value, days)
{
  const date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  const expires = "expires=" + date.toUTCString();

  document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

let cookiConsentButton = document.querySelector('[data-accept-cookies="true"]');
if (cookiConsentButton) {
  document.querySelector('[data-accept-cookies="true"]').addEventListener('click', function() {
    setCookie('cookie-consent', 'true', 1825);
    // console.log('Cookies aceitos!');
  });
}



/*
 * Open message.
 */
window.open_message = function (what, content)
{
  // mount HTML
  var container = document.createElement('div');
  container.innerHTML = content || '';
  document.body.appendChild(container);

  // resolve target (.modal | .toast) & delay
  var target =
    (what === 'modal' ? container.querySelector('.modal') : container.querySelector('.toast')) ||
    container.firstElementChild;

  if (!target) { container.remove(); return; }

  var delayAttr = parseInt(target.getAttribute('delay') || container.firstElementChild?.getAttribute?.('delay') || '0', 10);
  var delay = isNaN(delayAttr) ? 0 : Math.max(0, delayAttr);

  // type
  if (what === 'modal') {
    if (!target.classList.contains('modal')) { container.remove(); return; }
    var modal = new bootstrap.Modal(target);
    setTimeout(function(){ modal.show(); }, delay);
    target.addEventListener('hidden.bs.modal', function(){ container.remove(); });
  }
  else if (what === 'toast') {
    container.classList.add('toast-container');
    if (!target.classList.contains('toast')) { container.remove(); return; }
    var toast = new bootstrap.Toast(target);
    setTimeout(function(){ toast.show(); }, delay);
    target.addEventListener('hidden.bs.toast', function(){ container.remove(); });
  }
  else {
    container.remove(); // unknow title
  }
};



/**
 *
 * Search Brazilian's zipcodes.
 *
 */
function setInputValue(selector, value) {
  var el = document.querySelector(selector);
  if (el) el.value = value || '';
}

async function search_zipcode() {
  var zipInput = document.querySelector('#zipcode');
  if (!zipInput) return;

  var cep = (zipInput.value || '').replace(/\D/g, '');
  if (cep.length !== 8) {
    alert('CEP inválido');
    return;
  }

  try {
    var res = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
    if (!res.ok) throw new Error('Falha ao consultar CEP');

    var data = await res.json();

    if (data && !data.erro) {
      setInputValue('#street',   data.logradouro);
      setInputValue('#district', data.bairro);
      setInputValue('#city',     data.localidade);
      setInputValue('#state',    data.uf);
    } else {
      alert('CEP não encontrado');
    }
  } catch (err) {
    console.error(err);
    alert('Não foi possível consultar o CEP no momento.');
  }
}

// liga no blur (compatível)
var zipcodeEl = document.querySelector('#zipcode');
if (zipcodeEl) zipcodeEl.addEventListener('blur', search_zipcode);


/**
 *
 * Data controller confirmation with modals
 *
 **/
document.addEventListener('click', async (e) =>
{
  const a = e.target.closest('a[data-controller]');
  if (!a) return;

  e.preventDefault();
  const href = a.getAttribute('href') || '';
  const controller = a.dataset.controller;
  const params = new URLSearchParams(href.split('?')[1] || '');
  const foreign_key = params.get('foreign_key');

  const body = new FormData();
  body.append('action', href);
  body.append('controller', controller);
  if (foreign_key) body.append('foreign_key', foreign_key);

  const res = await fetch(`${BASE_URL}/${REST_API_BASE_ROUTE}/crud-data-controller`, { method: 'POST', body });
  const json = await res.json().catch(() => ({}));
  if (json?.detail?.msg) open_message(json.detail.type, json.detail.msg);
});


/**
 *
 * JS for status button.
 *
 **/
document.addEventListener('click', async (e) =>
{
  const btn = e.target.closest('[status-button]');
  if (!btn) return;

  const id   = (btn.id || '').replace('status-', '');
  const mode = btn.dataset.mode;
  const type = btn.getAttribute('status-button');
  btn.disabled = true;

  const body = new FormData();
  body.append('id', id);
  body.append('mode', mode);
  body.append('type', type);

  try {
    const res = await fetch(`${BASE_URL}/${REST_API_BASE_ROUTE}/switch-status-button`, { method: 'POST', body });
    const json = await res.json();
    if (json?.detail) open_message(json.detail.type, json.detail.msg);
    if (json?.button) btn.outerHTML = json.button;
  } finally {
    btn.disabled = false;
  }
});



/**
 *
 * Anchor
 *
 **/
document.addEventListener('click', (e) =>
{
  const link = e.target.closest('a[href^="#"]');
  if (!link) return;

  const href = link.getAttribute('href');

  e.preventDefault();

  if (!href || href === '#') return;

  let anchor;
  try {
    anchor = document.querySelector(href);
  } catch (err) {
    return;
  }

  if (!anchor) return;

  const headerHeight = document.querySelector('nav')?.offsetHeight || 0;
  const top = anchor.getBoundingClientRect().top + window.pageYOffset - headerHeight - 32;

  window.scrollTo({ top, behavior: 'smooth' });
});



/**
 *
 * Captura "submit" de qualquer form com [data-controller-form] (em captura p/ garantir)
 *
 */
document.addEventListener('submit', function (e)
{
  var form = e.target.closest('[data-controller-form]');
  if (!form) return;

  e.preventDefault();

  var form_action = form.getAttribute('action') || window.location.href;
  var form_method = (form.getAttribute('method') || 'POST').toUpperCase();
  var formData = new FormData(form);

  fetch(form_action, {
    method: form_method,
    body: formData
  })
  .then(function (res) { return res.text(); })
  .then(function (text) {
    var response;
    try {
      response = JSON.parse(text);
    } catch (err) {
      console.error('Resposta não-JSON do servidor:', text);
      alert('Erro inesperado no servidor. Tente novamente mais tarde.');
      return;
    }

    var redirect_delay = 0;

    if (response && response.detail && response.detail.msg !== undefined) {
      open_message(response.detail.type, response.detail.msg);
      redirect_delay = 2500;
    }

    if (response && response.redirect !== undefined) {
      setTimeout(function () {
        window.location.href = response.redirect;
      }, redirect_delay);
    }
  })
  .catch(function (err) {
    console.error('Falha na requisição:', err);
  });
}, true);


document.addEventListener('DOMContentLoaded', () =>
{
  //1) Busca normal
  document.querySelectorAll('select[data-search]').forEach((el) =>
  {
    if (el.tomselect) return;

    const allowCreate = el.hasAttribute('data-allow-create');

    new TomSelect(el, {
      create: allowCreate,
      plugins: ['dropdown_input'],
    });
  });

  // 2) Múltiplos
  document.querySelectorAll('select[data-search-multiple]').forEach((el) =>
  {
    if (el.tomselect) return;

    const min = parseInt(el.dataset.min || 0, 10);
    const max = parseInt(el.dataset.max || 0, 10);
    const allowCreate = el.hasAttribute('data-allow-create');

    const ts = new TomSelect(el, {
      create: allowCreate,
      maxItems: max || null,
      plugins: ['remove_button', 'dropdown_input'],
    });

    const form = el.closest('form');
    if (form && min > 0)
    {
      form.addEventListener('submit', (e) =>
      {
        if (ts.items.length < min) {
          e.preventDefault();
          alert(`Selecione no mínimo ${min} item(ns).`);
        }
      });
    }
  });
});



/**
 *
 * Return the value of the parameters about the actual URL.
 *
 */
var get_url_parameter = function get_url_parameter(sParam)
{
  var sPageURL = window.location.search.substring(1),
  sURLVariables = sPageURL.split('&'),
  sParameterName,
  i;

  for (i = 0; i < sURLVariables.length; i++)
  {
    sParameterName = sURLVariables[i].split('=');
    if (sParameterName[0] === sParam) return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
  }
};


/**
 *
 * Verify required inputs if they're empty.
 *
 */
// Abre/mostra todos os contêineres ocultos que envolvem o elemento
// function revealContainersFor(el) {
//   if (!el) return;

//   // Colete a cadeia de pais até <body> e depois abra do mais externo p/ o mais interno
//   const chain = [];
//   let node = el;
//   while (node && node !== document.body) {
//     if (node.classList?.contains('modal'))           chain.push({type:'modal',     el: node});
//     if (node.classList?.contains('offcanvas'))       chain.push({type:'offcanvas', el: node});
//     if (node.classList?.contains('dropdown-menu'))   chain.push({type:'dropdown',  el: node});
//     if (node.classList?.contains('tab-pane'))        chain.push({type:'tab',       el: node});
//     if (node.classList?.contains('collapse'))        chain.push({type:'collapse',  el: node});
//     if (node.tagName === 'DETAILS')                  chain.push({type:'details',   el: node});
//     node = node.parentElement;
//   }
//   chain.reverse();

//   chain.forEach(({type, el}) => {
//     try {
//       if (type === 'modal' && window.bootstrap?.Modal) {
//         window.bootstrap.Modal.getOrCreateInstance(el, {backdrop:true}).show();
//       }
//       else if (type === 'offcanvas' && window.bootstrap?.Offcanvas) {
//         window.bootstrap.Offcanvas.getOrCreateInstance(el).show();
//       }
//       else if (type === 'dropdown') {
//         // Encontre o .dropdown mais próximo e abra seu .dropdown-toggle
//         const wrapper = el.closest('.dropdown');
//         const toggle  = wrapper?.querySelector('[data-bs-toggle="dropdown"], .dropdown-toggle');
//         if (toggle && window.bootstrap?.Dropdown) {
//           window.bootstrap.Dropdown.getOrCreateInstance(toggle).show();
//         } else {
//           // fallback: force classes
//           wrapper?.classList.add('show');
//           el.classList.add('show');
//           el.style.display = 'block';
//         }
//       }
//       else if (type === 'tab') {
//         const id = el.id;
//         if (id) {
//           const selector = `[data-bs-toggle="tab"][data-bs-target="#${CSS.escape(id)}"], [data-bs-toggle="tab"][href="#${CSS.escape(id)}"]`;
//           const tabToggler = document.querySelector(selector);
//           if (tabToggler && window.bootstrap?.Tab) {
//             window.bootstrap.Tab.getOrCreateInstance(tabToggler).show();
//           } else {
//             // fallback: ativa classes da tab-pane
//             el.classList.add('active', 'show');
//           }
//         }
//       }
//       else if (type === 'collapse' && window.bootstrap?.Collapse) {
//         window.bootstrap.Collapse.getOrCreateInstance(el, {toggle:false}).show();
//       }
//       else if (type === 'details') {
//         el.open = true;
//       }
//     } catch (e) {
//       // silencioso: não queremos quebrar o fluxo por causa de um tipo específico
//       console.warn('Reveal error:', type, e);
//     }
//   });
// }

function revealContainersFor(el)
{
  if (!el) return;

  // Collect the parent chain up to <body>, then open from outermost to innermost
  const chain = [];
  let node = el;
  while (node && node !== document.body) {
    if (node.classList?.contains('modal'))           chain.push({ type: 'modal',         el: node });
    if (node.classList?.contains('offcanvas'))       chain.push({ type: 'offcanvas',     el: node });
    if (node.classList?.contains('dropdown-menu'))   chain.push({ type: 'dropdown',      el: node });
    if (node.classList?.contains('tab-pane'))        chain.push({ type: 'tab',           el: node });
    if (node.classList?.contains('collapse'))        chain.push({ type: 'collapse',      el: node });
    if (node.tagName === 'DETAILS')                  chain.push({ type: 'details',       el: node });
    if (node.classList?.contains('carousel-item'))   chain.push({ type: 'carousel-item', el: node });
    node = node.parentElement;
  }
  chain.reverse();

  chain.forEach(({ type, el }) => {
    try {
      if (type === 'modal' && window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(el, { backdrop: true }).show();
      }
      else if (type === 'offcanvas' && window.bootstrap?.Offcanvas) {
        window.bootstrap.Offcanvas.getOrCreateInstance(el).show();
      }
      else if (type === 'dropdown') {
        // Find the closest .dropdown and open its .dropdown-toggle
        const wrapper = el.closest('.dropdown');
        const toggle  = wrapper?.querySelector('[data-bs-toggle="dropdown"], .dropdown-toggle');
        if (toggle && window.bootstrap?.Dropdown) {
          window.bootstrap.Dropdown.getOrCreateInstance(toggle).show();
        } else {
          // Fallback: force classes
          wrapper?.classList.add('show');
          el.classList.add('show');
          el.style.display = 'block';
        }
      }
      else if (type === 'tab') {
        const id = el.id;
        if (id) {
          const selector =
            `[data-bs-toggle="tab"][data-bs-target="#${CSS.escape(id)}"], ` +
            `[data-bs-toggle="tab"][href="#${CSS.escape(id)}"]`;
          const tabToggler = document.querySelector(selector);
          if (tabToggler && window.bootstrap?.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(tabToggler).show();
          } else {
            // Fallback: activate tab-pane classes
            el.classList.add('active', 'show');
          }
        }
      }
      else if (type === 'collapse' && window.bootstrap?.Collapse) {
        window.bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
      }
      else if (type === 'details') {
        el.open = true;
      }
      else if (type === 'carousel-item') {
        // Ensure this slide is visible inside its carousel
        const carousel = el.closest('.carousel');
        if (!carousel) return;

        const items = Array.from(carousel.querySelectorAll('.carousel-item'));
        const index = items.indexOf(el);

        if (window.bootstrap?.Carousel) {
          const inst = window.bootstrap.Carousel.getOrCreateInstance(carousel);
          if (index >= 0) {
            inst.to(index);
          }
        } else {
          // Fallback: simple class switch
          items.forEach(i => i.classList.remove('active'));
          el.classList.add('active');
        }
      }
    } catch (e) {
      // Silent: we don't want to break the whole flow for a single type
      console.warn('Reveal error:', type, e);
    }
  });
}


/**
 * Validates required fields inside a form.
 *
 * @param {HTMLFormElement|HTMLElement} form - The root form.
 * @param {Object} [options]
 * @param {HTMLElement|null} [options.scope=null] - If provided, only validates fields inside this container.
 * @param {boolean} [options.scrollToFirstInvalid=true] - Scrolls to the first invalid field if any.
 * @param {boolean} [options.revealContainers=true] - Calls revealContainersFor on the first invalid field, if available.
 * @returns {{ allFieldsValid: boolean, firstInvalid: HTMLElement|null }}
 */
/**
 * Validates fields inside a form using native constraint validation first,
 * while keeping special rules for:
 * - hidden upload JSON inputs ([input-files])
 * - required radio/checkbox groups
 *
 * It also prevents "not focusable" by:
 * - revealing parent containers
 * - focusing a truly focusable proxy (TomSelect, visible upload UI, etc.)
 *
 * @param {HTMLFormElement} form
 * @param {Object} [options]
 * @param {HTMLElement|null} [options.scope=null]
 * @param {boolean} [options.scrollToFirstInvalid=true]
 * @param {boolean} [options.revealContainers=true]
 * @returns {{ allFieldsValid: boolean, firstInvalid: HTMLElement|null }}
 */
function validateRequiredFields(form, options)
{
  const opts = Object.assign({
    scope: null,
    scrollToFirstInvalid: true,
    revealContainers: true
  }, options || {});

  const root = opts.scope || form;

  // Helper: visible check (prevents "not focusable")
  function isVisible(el) {
    if (!el || el.nodeType !== 1) return false;
    if (el.type === 'hidden') return false;
    const cs = window.getComputedStyle(el);
    if (cs.display === 'none' || cs.visibility === 'hidden') return false;
    // offsetParent null can be false-positive for fixed; still ok as extra guard
    if (el.offsetParent === null && cs.position !== 'fixed') return false;
    return true;
  }

  // Helper: choose a focusable target for a given invalid control
  function resolveFocusableTarget(el)
  {
    if (!el) return null;

    // Custom upload: focus the visible UI, not the hidden JSON input
    if (el.hasAttribute('input-files')) {
      const filesWrap = el.closest('.files');
      const profilePhoto = filesWrap?.querySelector('label.box.profile .photo');
      const addFile = filesWrap?.querySelector('.add-file');
      return profilePhoto || addFile || filesWrap || null;
    }

    // TomSelect: <select> becomes hidden, focus its control
    if (el.tagName === 'SELECT' && el.tomselect) {
      // control_input is usually the best focus target
      return el.tomselect.control_input || el.tomselect.control || null;
    }

    // Normal field
    return el;
  }

  // Helper: mark invalid UI classes + optional bootstrap feedback
  function markInvalid(el, message)
  {
    if (!el) return;

    el.classList.add('is-invalid');
    const ig = el.closest('.input-group');
    if (ig) ig.classList.add('is-invalid');

    // Keep your existing feedback behavior when we have a message
    if (message) {
      try { applyInvalidFeedback(el, message); } catch (_) {}
    }
  }

  function clearInvalid(el)
  {
    if (!el) return;
    el.classList.remove('is-invalid');
    const ig = el.closest('.input-group');
    if (ig) ig.classList.remove('is-invalid');
  }

  // --- 1) Special cases: required radio/checkbox groups + upload JSON fields ---
  let firstInvalid = null;
  let allFieldsValid = true;

  const processedGroups = new Set();

  // Validate required radio/checkbox groups inside scope
  root.querySelectorAll('input[required][type="radio"], input[required][type="checkbox"]').forEach((el) =>
  {
    const name = el.name || '';
    const key = el.type + '::' + name;

    if (name) {
      if (processedGroups.has(key)) return;
      processedGroups.add(key);

      const group = root.querySelectorAll(`input[type="${el.type}"][name="${CSS.escape(name)}"]`);
      const ok = Array.prototype.some.call(group, i => i.checked);

      if (!ok) {
        allFieldsValid = false;
        markInvalid(el, el.validationMessage || 'Please select an option.');
        if (!firstInvalid) firstInvalid = el;
      } else {
        // clear group marks
        Array.prototype.forEach.call(group, clearInvalid);
      }
    } else {
      if (!el.checked) {
        allFieldsValid = false;
        markInvalid(el, el.validationMessage || 'Please select an option.');
        if (!firstInvalid) firstInvalid = el;
      } else {
        clearInvalid(el);
      }
    }
  });

  // Validate upload hidden JSON inputs that are required (or data-required turned into required earlier)
  root.querySelectorAll('input[input-files]').forEach((el) =>
  {
    // only enforce if required
    if (!el.hasAttribute('required')) return;

    let arr = [];
    try { arr = JSON.parse(el.value || '[]'); } catch (_) { arr = []; }
    const ok = Array.isArray(arr) && arr.length > 0;

    if (!ok) {
      allFieldsValid = false;
      markInvalid(el, 'Please select at least one file.');
      if (!firstInvalid) firstInvalid = el;
    } else {
      clearInvalid(el);
    }
  });

  // --- 2) Native HTML5 constraints: find first :invalid control (works for minlength/maxlength/step/pattern/type/etc.) ---
  // We validate only controls that the browser considers validatable.
  const controls = root.querySelectorAll('input, select, textarea');

  controls.forEach((el) =>
  {
    // Skip disabled + non-validatable
    if (!el || el.disabled) return;

    // Skip file inputs that are “mirrored” by your custom [input-files] in the same .files wrapper
    if (
      el.type === 'file' &&
      el.closest('.files') &&
      el.closest('.files').querySelector('input[input-files]')
    ) {
      return;
    }

    // Skip radio/checkbox here (handled above when required)
    if (el.type === 'radio' || el.type === 'checkbox') return;

    // Skip custom upload hidden (handled above)
    if (el.hasAttribute('input-files')) return;

    // If browser can validate it, use it
    if (el.willValidate) {
      const ok = el.checkValidity();

      if (!ok) {
        allFieldsValid = false;
        markInvalid(el, el.validationMessage || '');
        if (!firstInvalid) firstInvalid = el;
      } else {
        clearInvalid(el);
      }
    }
  });

  // --- 3) Reveal + focus + native bubble (when possible) ---
  if (!allFieldsValid && firstInvalid)
  {
    if (opts.revealContainers && typeof revealContainersFor === 'function') {
      try { revealContainersFor(firstInvalid); } catch (_) {}
    }

    const focusTarget = resolveFocusableTarget(firstInvalid);

    if (opts.scrollToFirstInvalid) {
      setTimeout(() =>
      {
        const scrollTarget =
          focusTarget?.closest?.('.input-group') || focusTarget || firstInvalid;

        try { scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (_) {}

        // Focus only if it is actually focusable/visible
        try {
          if (focusTarget && isVisible(focusTarget) && typeof focusTarget.focus === 'function') {
            focusTarget.focus({ preventScroll: true });
          }
        } catch (_) {}

        // Try native message bubble on the real control
        // (If it's hidden, reportValidity may throw; then we fallback to bootstrap feedback already applied)
        try {
          if (firstInvalid.willValidate && typeof firstInvalid.reportValidity === 'function') {
            firstInvalid.reportValidity();
          }
        } catch (_) {}
      }, 60);
    } else {
      try {
        if (firstInvalid.willValidate && typeof firstInvalid.reportValidity === 'function') {
          firstInvalid.reportValidity();
        }
      } catch (_) {}
    }
  }

  return { allFieldsValid, firstInvalid };
}




// Validation + automatic opening of containers for the first invalid field
document.addEventListener('click', function (e) {
    const submitBtn = e.target.closest('[data-send-without-reload] [type="submit"]');
    if (!submitBtn) return;

    const form = submitBtn.closest('[data-send-without-reload]');
    if (!form) return;

    const { allFieldsValid } = validateRequiredFields(form, {
        scope: null, // whole form
        scrollToFirstInvalid: true,
        revealContainers: true
    });

    if (!allFieldsValid) {
        e.preventDefault();
        e.stopPropagation();
    }
});


/*
 * Revalidate the inputs when the user types.
 */
document.addEventListener('input', function(e)
{
  const input = e.target;

  // Only react on elements that already have validation state
  if (!input.classList.contains('is-invalid') && !input.classList.contains('is-valid')) {
    return;
  }

  const isUploadHidden = input.hasAttribute('input-files');
  const inputGroup = input.closest('.input-group');

  if (isUploadHidden) {
    // Upload hidden field revalidation (images, videos, archives, audios)
    let arr = [];
    try {
      arr = JSON.parse(input.value || '[]');
    } catch (_) {
      arr = [];
    }

    const invalid = !Array.isArray(arr) || arr.length === 0;

    // Toggle classes on the hidden input itself
    if (invalid) {
      input.classList.add('is-invalid');
      input.classList.remove('is-valid');
      if (inputGroup) {
        inputGroup.classList.add('is-invalid');
        inputGroup.classList.remove('is-valid');
      }
    } else {
      input.classList.remove('is-invalid');
      input.classList.add('is-valid');
      if (inputGroup) {
        inputGroup.classList.remove('is-invalid');
        inputGroup.classList.add('is-valid');
      }
    }

    const filesWrap  = input.closest('.files');
    const uploadType = (input.dataset.type || '').toLowerCase(); // images, archives, videos, audios

    const profilePhoto = filesWrap?.querySelector('label.box.profile .photo');
    if (profilePhoto) {
      if (invalid) {
        profilePhoto.classList.add('is-invalid');
        profilePhoto.classList.remove('is-valid');
      } else {
        profilePhoto.classList.remove('is-invalid');
        profilePhoto.classList.add('is-valid');
      }
      return;
    }

    if (uploadType === 'audios')
    {
      const controls = filesWrap?.querySelector('.audio-controls');
      if (controls) {
        if (invalid) {
          controls.classList.add('is-invalid');
          controls.classList.remove('is-valid');
        } else {
          controls.classList.remove('is-invalid');
          controls.classList.add('is-valid');
        }
      }
    } else {
      // Other uploads → .add-file
      const addFile = filesWrap?.querySelector('.add-file');
      if (addFile) {
        if (invalid) {
          addFile.classList.add('is-invalid');
          addFile.classList.remove('is-valid');
        } else {
          addFile.classList.remove('is-invalid');
          addFile.classList.add('is-valid');
        }
      }
    }

    return;
  }

  if (input.value.length === 0) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
    if (inputGroup) {
      inputGroup.classList.add('is-invalid');
      inputGroup.classList.remove('is-valid');
    }
  } else {
    input.classList.add('is-valid');
    input.classList.remove('is-invalid');
    if (inputGroup) {
      inputGroup.classList.add('is-valid');
      inputGroup.classList.remove('is-invalid');
    }
  }
}, true);


/**
 * Marks a field as invalid and attaches/updates a Bootstrap .invalid-feedback element.
 *
 * Regras:
 * - Se tiver .input-group: aplica .is-invalid no input + .input-group
 *   e cria/usa .invalid-feedback associado ao grupo (irmão do .input-group).
 * - Se não tiver .input-group, mas tiver .form-floating:
 *   cria/usa .invalid-feedback dentro do .form-floating.
 * - Fallback: cria .invalid-feedback logo após o input.
 *
 * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} input
 * @param {string} message
 */
function applyInvalidFeedback(input, message)
{
  if (!input) return;

  // Sempre marca o próprio campo
  input.classList.add('is-invalid');

  const inputGroup  = input.closest('.input-group');
  const formFloating = input.closest('.form-floating');
  let feedback = null;

  // --- CASO 1: existe .input-group ---
  if (inputGroup)
  {
    // Marca o grupo como inválido também
    inputGroup.classList.add('is-invalid');

    // 1) tenta encontrar feedback dentro do input-group
    feedback = inputGroup.querySelector('.invalid-feedback');

    const parent = inputGroup.parentElement;

    // 2) se não tiver dentro, tenta achar como irmão logo depois do input-group
    if (!feedback && parent) {
      for (let sib = inputGroup.nextElementSibling; sib; sib = sib.nextElementSibling) {
        if (sib.classList && sib.classList.contains('invalid-feedback')) {
          feedback = sib;
          break;
        }
      }
    }

    // 3) se ainda não existir, cria como irmão do input-group
    if (!feedback && parent) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';

      // insere antes de <small>, se existir
      let insertBefore = null;
      for (let sib = inputGroup.nextElementSibling; sib; sib = sib.nextElementSibling) {
        if (sib.tagName && sib.tagName.toLowerCase() === 'small') {
          insertBefore = sib;
          break;
        }
      }

      if (insertBefore) {
        parent.insertBefore(feedback, insertBefore);
      } else if (inputGroup.nextSibling) {
        parent.insertBefore(feedback, inputGroup.nextSibling);
      } else {
        parent.appendChild(feedback);
      }
    }

  // --- CASO 2: não tem .input-group, mas tem .form-floating ---
  } else if (formFloating) {

    // feedback fica DENTRO do .form-floating
    feedback = formFloating.querySelector('.invalid-feedback');

    if (!feedback) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      formFloating.appendChild(feedback);
    }

  // --- Fallback: sem input-group e sem form-floating ---
  } else {
    const parent = input.parentElement;
    if (!parent) return;

    // tenta achar algum .invalid-feedback como irmão
    feedback = Array.from(parent.children).find(function (el) {
      return el.classList && el.classList.contains('invalid-feedback');
    }) || null;

    if (!feedback) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      if (input.nextSibling) {
        parent.insertBefore(feedback, input.nextSibling);
      } else {
        parent.appendChild(feedback);
      }
    }
  }

  if (feedback) {
    const msg = String(message ?? '');

    // Se vier com tag, renderiza HTML; senão, texto normal
    if (/<[a-z][\s\S]*>/i.test(msg)) feedback.innerHTML = msg;
    else feedback.textContent = msg;
  }
}



/**
 *
 * The function that makes ALL the forms in this plataform works.
 *
 */
async function send_form(form)
{
  const delay  = Number(form.dataset.formDelay || 0);
  const action = form.getAttribute('action');
  const method = form.getAttribute('method') || 'POST';
  const body   = new FormData(form);

  const submits = form.querySelectorAll('[type="submit"]');
  const spins = [];

  let willRedirect = false;

  submits.forEach(btn =>
  {
    btn.setAttribute('disabled', 'true');

    const spin = btn.querySelector('.spinner-border-sm');
    spins.push(spin);

    if (spin) spin.style.display = 'inline-block';
  });

  await new Promise(r => setTimeout(r, delay));

  try
  {
    const res  = await fetch(action, { method, body });
    const text = await res.text();

    let json;
    try { json = JSON.parse(text); }
    catch { throw new Error('Invalid JSON Response'); }

    if (json.detail?.msg) open_message(json.detail.type, json.detail.msg || '');

    if (json?.redirect)
    {
      willRedirect = true;
      form.classList.add('is-redirecting');

      setTimeout(() => { window.location.href = json.redirect; }, 2500);
      return json ?? [];
    }

    if (json?.token)
    {
      let tokenInput = form.querySelector('input[name="token"]');

      if (!tokenInput)
      {
        tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'token';
        form.appendChild(tokenInput);
      }

      tokenInput.value = json.token;
    }

    if (json?.invalid_inputs)
    {
      Object.entries(json.invalid_inputs).forEach(([name, msg]) =>
      {
        const input = document.querySelector(`[name="${name}"]`);
        if (!input) return;

        applyInvalidFeedback(input, msg);
      });
    }

    if (json?.processed_ids)
    {
      Object.entries(json.processed_ids).forEach(([table, ids]) =>
      {
        const repeater = document.querySelector(`[data-repeater-to="${table}"]`);
        if (!repeater) return;

        repeater.querySelectorAll('tr[data-index]:not([data-template])').forEach((row, i) =>
        {
          const input = row.querySelector('input[name*="[id]"]');
          if (input && ids[i] != null) input.value = ids[i];
        });
      });
    }

    return json ?? [];
  }
  catch (err)
  {
    console.error(err);
    alert('Erro inesperado no servidor. Por favor, contate o suporte.');
  }
  finally
  {
    if (willRedirect) return;

    submits.forEach(btn => btn.removeAttribute('disabled'));
    spins.forEach(spin => { if (spin) spin.style.display = 'none'; });
  }
}



/*
 * Send form without reload with CTRL+s.
 */
document.addEventListener('keydown', (e) =>
{
  if (e.ctrlKey && e.key === 's') {
    e.preventDefault();
    const form = document.querySelector('[data-send-ctrl-s]');
    if (form) send_form(form);
  }
});

/*
 * Send form without reload.
 */
document.addEventListener('submit', (e) =>
{
  const form = e.target.closest('[data-send-without-reload]');
  if (!form) return;
  e.preventDefault();
  send_form(form);
});


/**
 *
 * Drag 'n drop system.
 *
 */
function dragging_get_new_position(column, posY) {
  const cards = column.querySelectorAll(":scope > .draggable-item:not(.dragging)");
  let result;

  for (let refer_card of cards) {
    const box = refer_card.getBoundingClientRect();
    const boxCenterY = box.y + box.height / 2;
    if (posY >= boxCenterY) result = refer_card;
  }

  return result;
}

document.addEventListener("dragstart", (e) => {
  const target = e.target.closest(".draggable-item");
  if (target) {
    target.classList.add("dragging");
    target.setAttribute("draggable", "true");
  }
});

document.addEventListener("dragend", (e) => {
  const target = e.target.closest(".draggable-item");
  if (target) {
    target.classList.remove("dragging");
    target.removeAttribute("draggable");
  }
});

document.addEventListener("dragover", (e) => {
  const dragging = document.querySelector(".dragging");
  if (!dragging) return;

  const column = e.target.closest(".draggable-column");
  if (!column) return;

  const parentController = dragging.closest(".draggable-column");
  if (column === parentController) {
    const applyAfter = dragging_get_new_position(column, e.clientY);

    if (applyAfter) {
      column.insertBefore(dragging, applyAfter.nextSibling);
    } else {
      column.prepend(dragging);
    }
  }
});

document.addEventListener("mouseover", (e) => {
  if (e.target.closest(".move")) {
    const draggableItem = e.target.closest(".draggable-item");
    if (draggableItem) {
      draggableItem.setAttribute("draggable", "true");
    }
  }
});

document.addEventListener("mouseout", (e) => {
  if (e.target.closest(".move")) {
    const draggableItem = e.target.closest(".draggable-item");
    if (draggableItem) {
      draggableItem.removeAttribute("draggable");
    }
  }
});


// ----- Step forms -----
document.querySelectorAll('[step-form]').forEach(function (form)
{
    const one_step_at_a_time = form.getAttribute('one-step-at-a-time') === 'true';
    const save_between_steps = form.getAttribute('save-between-steps') === 'true';

    const total_steps = parseInt(form.querySelector('[name="total_steps"]')?.value || '1', 10);

    const actualStepInput = form.querySelector('[name="actual_step"]');
    let currentStep = actualStepInput ? parseInt(actualStepInput.value || '1', 10) : 1;

    // Agora são NodeLists
    const prevBtns = Array.from(form.querySelectorAll('[data-bs-slide="prev"]'));
    const nextBtns = Array.from(form.querySelectorAll('[data-bs-slide="next"]'));
    const sendBtns = Array.from(form.querySelectorAll('[send-button]'));

    // Se precisar do send-name, pega do primeiro NEXT
    const firstNextBtn = nextBtns[0] || form.querySelector('[data-bs-slide="next"]');
    const sendName = firstNextBtn ? firstNextBtn.getAttribute('send-name') : null;

    // Status UI (podem existir 2x no form)
    const savingEls = Array.from(form.querySelectorAll('.saving-step'));
    const savedEls  = Array.from(form.querySelectorAll('.step-saved'));

    function showSaving() {
      savingEls.forEach(el => { el.style.display = ''; });
    }

    function hideSaving() {
      savingEls.forEach(el => { el.style.display = 'none'; });
    }

    function flashSaved()
    {
      if (form._savedTimer) clearTimeout(form._savedTimer);
      if (form._savedHideTimer) clearTimeout(form._savedHideTimer);

      // mostra
      savedEls.forEach(el => {
        el.classList.remove('is-hiding');
        el.style.display = '';

        void el.offsetWidth;
      });

      // segura 5s e faz fade-out
      form._savedTimer = setTimeout(() =>
      {
        savedEls.forEach(el => el.classList.add('is-hiding'));
        form._savedHideTimer = setTimeout(() => {
          savedEls.forEach(el => {
            el.classList.remove('is-hiding');
            el.style.display = 'none';
          });
        }, 300);

      }, 5000);
    }

    // Instância do Carousel (se existir bootstrap.Carousel)
    let carousel = null;
    if (typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
        try {
            carousel = bootstrap.Carousel.getOrCreateInstance(form);
        } catch (e) {
            console.warn('Carousel instance not available for step-form:', e);
        }
    }

    /**
     * Atualiza a UI dos componentes de progresso do form
     * - Modelo 1: .progress_steps_detailed
     * - Modelo 2: .progress-content simples (p + .progress-bar)
     */
    function updateFormProgress(step)
    {
        const safeStep = Math.max(1, Math.min(total_steps || 1, step || 1));

        // --- Modelo 1: detalhado (bolinhas + barra superior) ---
        const detailed = form.querySelector('.progress_steps_detailed');
        if (detailed)
        {
            detailed.setAttribute('data-active', String(safeStep));
            detailed.setAttribute('data-total', String(total_steps));

            const topBar = detailed.querySelector('.progress');
            if (topBar) {
                const segments = Math.max(1, (total_steps || 1) - 1);
                const percent = segments > 0 ? ((safeStep - 1) / segments) * 100 : 0;
                topBar.style.width = percent + '%';
            }

            const circles = detailed.querySelectorAll('.progress-step-circle');
            circles.forEach(function (circle, idx) {
                if (idx < safeStep) {
                    circle.classList.add('progress-step-active');
                } else {
                    circle.classList.remove('progress-step-active');
                }
            });
        }

        // --- Modelo 2: simples (2/4 + progress bar) ---
        const simpleProgressBlocks = form.querySelectorAll('.progress-content:not(.progress_steps_detailed)');
        simpleProgressBlocks.forEach(function (block) {
            const label = block.querySelector('p');
            if (label) {
                label.textContent = safeStep + '/' + (total_steps || 1);
            }

            const bar = block.querySelector('.progress-bar');
            if (bar) {
                const pct = (total_steps > 0) ? ((safeStep / total_steps) * 100) : 0;
                bar.style.width = pct + '%';
                bar.setAttribute('aria-valuenow', String(Math.round(pct)));
            }
        });
    }

    /**
     * Controla visualmente o estado dos Buttons prev/next/send
     * Aplica a lógica para TODOS os Buttons do formulário
     */
    function updateNavigationButtons()
    {
        // PREV
        prevBtns.forEach(function (btn) {
            if (currentStep <= 1) {
                btn.setAttribute('disabled', 'disabled');
            } else {
                btn.removeAttribute('disabled');
            }
        });

        // NEXT
        nextBtns.forEach(function (btn) {
            if (currentStep >= total_steps) {
                if (btn.parentElement) {
                    btn.parentElement.classList.add('d-none');
                } else {
                    btn.classList.add('d-none');
                }
            } else {
                if (btn.parentElement) {
                    btn.parentElement.classList.remove('d-none');
                } else {
                    btn.classList.remove('d-none');
                }
            }
        });

        // SEND
        sendBtns.forEach(function (btn) {
            if (currentStep < total_steps) {
                if (btn.parentElement) {
                    btn.parentElement.classList.add('d-none');
                } else {
                    btn.classList.add('d-none');
                }
            } else {
                if (btn.parentElement) {
                    btn.parentElement.classList.remove('d-none');
                } else {
                    btn.classList.remove('d-none');
                }
            }
        });
    }

    /**
     * Atualiza o passo atual, input hidden, Buttons e progresso
     */
    function updateCurrentStep(newStep)
    {
        currentStep = Math.max(1, Math.min(total_steps || 1, newStep || 1));

        if (actualStepInput) {
            actualStepInput.value = currentStep;
        }

        updateNavigationButtons();
        updateFormProgress(currentStep);
    }

    /**
     * Avança/retrocede o carousel (se existir) e atualiza o step
     */
    function goToNextStep()
    {
        if (currentStep >= total_steps) return;

        updateCurrentStep(currentStep + 1);

        if (carousel) {
            try {
                carousel.next();
            } catch (e) {
                console.warn('Erro ao avançar carousel:', e);
            }
        }
    }

    function goToPrevStep()
    {
        if (currentStep <= 1) return;

        updateCurrentStep(currentStep - 1);

        if (carousel) {
            try {
                carousel.prev();
            } catch (e) {
                console.warn('Erro ao voltar carousel:', e);
            }
        }
    }

    // Inicializa estado dos Buttons e progresso na carga
    updateCurrentStep(currentStep);

    async function callSendForm(form)
    {
      try
      {
        // May or may not return a Promise
        let res = send_form(form);

        // If it's a Promise, await it
        if (res && typeof res.then === 'function') {
          res = await res;
          // console.log('send_form async result:', res);

          // If API returns a structured response, respect it
          if (!res || res.code !== 'success') {
            return { ok: false, data: res };
          }
        } else {
          // Legacy behavior: no Promise / no explicit result
          // We assume "ok" to avoid blocking the step
          // console.log('send_form returned (sync):', res);
        }

        return { ok: true, data: res };
      } catch (e) {
        console.error('Error in send_form:', e);
        return { ok: false, error: e };
      }
    }


    /**
     * Buttons PREV
     */
    prevBtns.forEach(function (prevBtn) {
      prevBtn.addEventListener('click', function (event)
      {
        // Também bloqueia o comportamento padrão e controla manualmente
        event.preventDefault();
        event.stopPropagation();

        goToPrevStep();
      });
    });


    /**
     * Buttons NEXT
     */
    nextBtns.forEach(function (nextBtn) {
      nextBtn.addEventListener('click', async function (event)
      {
        // Always cancel the default data-bs-slide behavior
        event.preventDefault();
        event.stopPropagation();

        // Do not go beyond the last step
        if (currentStep >= total_steps) {
          return;
        }

        // If only one step at a time is allowed, validate the current step before proceeding
        if (one_step_at_a_time)
        {
          const currentStepContainer =
            form.querySelector('.carousel-item.active') || form;

          const { allFieldsValid } = validateRequiredFields(form, {
            scope: currentStepContainer,
            scrollToFirstInvalid: true,
            revealContainers: true
          });

          if (!allFieldsValid) {
            return;
          }
        }

        // If we need to save between steps, only advance if send_form() doesn't fail
        if (save_between_steps) {
          showSaving();

          const { ok } = await callSendForm(form);

          hideSaving();

          if (!ok) {
            console.warn('send_form failed — step will not advance.');
            return;
          }

          flashSaved();
        }

        // Finally advance step
        goToNextStep();
      });
    });


    /**
     * Buttons Send
     */
    sendBtns.forEach(function (sendBtn) {
      sendBtn.addEventListener('click', async function (event)
      {
        event.preventDefault();
        event.stopPropagation();

        // validate the current step before proceeding
        const currentStepContainer =
          form.querySelector('.carousel-item.active') || form;

        const { allFieldsValid } = validateRequiredFields(form, {
          scope: currentStepContainer,
          scrollToFirstInvalid: true,
          revealContainers: true
        });

        if (!allFieldsValid) {
          return;
        }

        const { ok } = await callSendForm(form);
        if (!ok) {
          console.warn('send_form failed — submit blocked.');
          return;
        }
      });
    });

});


/**
 *
 * Copy input value.
 *
 */
document.addEventListener('click', function (e)
{
  const button = e.target.closest('[copy-content]');
  if (!button) return;

  const input = button.closest('.input-group').querySelector('input');
  const icon = button.querySelector('.icon');

  if (!input || !icon) return;

  const text = input.value;

  navigator.clipboard.writeText(text).then(() =>
  {
    icon.classList.remove('fa-copy');
    icon.classList.add('fa-check');

    setTimeout(() => {
      icon.classList.remove('fa-check');
      icon.classList.add('fa-copy');
    }, 1000);
  }).catch(err => {
    console.error('It was not possible to copy', err);
  });
});


/**
 * Field Repeater (delegated + dynamic)
 * - Works for repeaters present at load time and those injected later
 */
(function ()
{
  // --- Helpers --------------------------------------------------------------

  function getEls(wrapper) {
    var container = wrapper.querySelector('.repeater-rows tbody')
                  || wrapper.querySelector('.repeater-rows')
                  || wrapper;
    var template  = wrapper.querySelector('tr[data-template]');
    var addBtn    = wrapper.querySelector('.add-repeater-row');
    var maxRows   = parseInt(wrapper.getAttribute('data-max-rows'), 10);
    if (isNaN(maxRows)) maxRows = null;
    return { container: container, template: template, addBtn: addBtn, maxRows: maxRows };
  }

  function getRows(wrapper) {
    var els = getEls(wrapper);
    return Array.prototype.slice.call(
      els.container.querySelectorAll('tr[data-index]:not([data-template])')
    );
  }

  function ensureUniqueIndex(wrapper) {
    if (!wrapper.dataset.uniqueIndex) {
      var cnt = getEls(wrapper).container.querySelectorAll('tr[data-index]:not([data-template]) input').length;
      wrapper.dataset.uniqueIndex = String(cnt || 0);
    }
  }

  function updateMoveButtons(wrapper) {
    var rows = getRows(wrapper);
    rows.forEach(function (row, i) {
      var upBtn   = row.querySelector('.btn-move.up');
      var downBtn = row.querySelector('.btn-move.down');
      if (upBtn)   upBtn.disabled   = (i === 0);
      if (downBtn) downBtn.disabled = (i === rows.length - 1);
    });
  }

  function checkAddButton(wrapper) {
    var els = getEls(wrapper);
    if (!els.addBtn) return;
    if (els.maxRows === null) { els.addBtn.disabled = false; return; }
    els.addBtn.disabled = (getRows(wrapper).length >= els.maxRows);
  }

  function updateKeys(wrapper) {
    var rows = getRows(wrapper);
    rows.forEach(function (row, i) {
      row.setAttribute('data-index', i);
      var idx = row.querySelector('.index-number');
      if (idx) idx.textContent = i + 1;
    });
    updateMoveButtons(wrapper);
    checkAddButton(wrapper);
  }

  // --- Mask applier (safe wrapper) -----------------------------------------
  function applyMasksSafe(root) {
    // usa o aplicador global que você já criou no outro script
    if (typeof window.applyMasks === 'function') {
      window.applyMasks(root || document);
    }
  }

  function addRow(wrapper) {
    var els = getEls(wrapper);
    if (!els.template || !els.container) return;

    var count = getRows(wrapper).length;
    if (els.maxRows !== null && count >= els.maxRows) return;

    ensureUniqueIndex(wrapper);
    var unique = parseInt(wrapper.dataset.uniqueIndex, 10) || 0;

    var newRow = els.template.cloneNode(true);
    newRow.classList.remove('template-row');
    newRow.removeAttribute('data-template');
    newRow.style.display = '';
    newRow.setAttribute('data-index', count);

    // Replace placeholders
    var html = newRow.innerHTML
      .replace(/__index_number__/g, String(count + 1))
      .replace(/__index__/g, String(count))
      .replace(/__index_input__/g, String(unique));
    wrapper.dataset.uniqueIndex = String(unique + 1);
    newRow.innerHTML = html;

    // Remove "init flags" that were copied from the template
    Array.prototype.forEach.call(newRow.querySelectorAll('[data-imask-init],[data-money-init]'), function (el) {
      el.removeAttribute('data-imask-init');
      el.removeAttribute('data-money-init');
      if (el.dataset) {
        delete el.dataset.imaskInit;
        delete el.dataset.moneyInit;
      }
    });

    // Activate required fields (were marked as data-required in template)
    Array.prototype.forEach.call(newRow.querySelectorAll('[data-required]'), function (input) {
      input.setAttribute('required', 'required');
      input.removeAttribute('data-required');
    });

    // Fade-in
    newRow.style.opacity = '0';
    newRow.style.transition = 'opacity 250ms ease';
    els.container.appendChild(newRow);

    // (repetidor / inputs dinâmicos / masks IMask / mask-money etc.)
    applyMasksSafe(newRow);

    requestAnimationFrame(function () { newRow.style.opacity = '1'; });

    updateKeys(wrapper);
  }

  function deleteRow(row) {
    row.style.transition = 'opacity 250ms ease';
    row.style.opacity = '0';
    setTimeout(function () { row.remove(); }, 250);
  }

  function moveRow(wrapper, row, dir) {
    var tbody = row.parentNode;
    var rows = getRows(wrapper);
    var i = rows.indexOf(row);
    if (dir === 'up' && i > 0) {
      tbody.insertBefore(row, rows[i - 1]);
    } else if (dir === 'down' && i < rows.length - 1) {
      var nextSibling = rows[i + 1].nextElementSibling;
      tbody.insertBefore(row, nextSibling);
    }
  }

  function initWrapper(wrapper) {
    ensureUniqueIndex(wrapper);
    updateKeys(wrapper);

    var tbody = getEls(wrapper).container;
    if (tbody) applyMasksSafe(tbody);
  }

  // --- Init existing repeaters ---------------------------------------------

  document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(document.querySelectorAll('[data-repeater]'), initWrapper);
  });

  // --- Observe dynamically added repeaters ---------------------------------

  var mo = new MutationObserver(function (mutations) {
    mutations.forEach(function (m) {
      Array.prototype.forEach.call(m.addedNodes || [], function (node) {
        if (!node || node.nodeType !== 1) return; // element only

        if (node.matches && node.matches('[data-repeater]')) initWrapper(node);

        var found = node.querySelectorAll ? node.querySelectorAll('[data-repeater]') : [];
        Array.prototype.forEach.call(found, initWrapper);
      });
    });
  });
  mo.observe(document.documentElement || document.body, { childList: true, subtree: true });

  // --- Delegated events (work for dynamic content) -------------------------

  document.addEventListener('click', function (e) {
    // Add row
    var addBtn = e.target.closest('.add-repeater-row');
    if (addBtn) {
      var wrapper = addBtn.closest('[data-repeater]');
      if (wrapper) addRow(wrapper);
      return;
    }

    // Delete row
    var delBtn = e.target.closest('[delete-row-from-repeater]');
    if (delBtn) {
      e.preventDefault();
      var row = delBtn.closest('tr[data-index]:not([data-template])');
      var wrapper = delBtn.closest('[data-repeater]');
      if (row && wrapper) {
        deleteRow(row);
        // update after the animation ends
        setTimeout(function () { updateKeys(wrapper); }, 260);
      }
      return;
    }

    // Move row (up/down)
    var moveBtn = e.target.closest('.btn-move');
    if (moveBtn && !moveBtn.closest('tr')?.hasAttribute('data-template')) {
      var wrapper = moveBtn.closest('[data-repeater]');
      var row = moveBtn.closest('tr[data-index]');
      if (wrapper && row) {
        if (moveBtn.classList.contains('up'))   moveRow(wrapper, row, 'up');
        if (moveBtn.classList.contains('down')) moveRow(wrapper, row, 'down');
        updateKeys(wrapper);
      }
    }
  });
})();
