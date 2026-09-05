/*TomSelect*/
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

/**
 * Endpoint responsável por resolver os dois formatos de busca ASYNC:
 *   - [field-id="{x}"]     -> tb_cruds_fields.Query
 *   - [field-search="{x}"] -> $GLOBALS['searchable-fields'][{x}]
 *
 * Ajuste este caminho caso o arquivo seja movido ou exista um router
 * na frente dele. `window.BASE_URL` é o mesmo valor já usado no PHP
 * (constante `BASE_URL`) para montar a URL deste próprio script.
 */
const BASE_URL = window.BASE_URL;
const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;
const SEARCHABLE_FIELDS_API_URL = `${BASE_URL}/${REST_API_BASE_ROUTE}/searchable-fields`;

/**
 * Monta a função `load` do TomSelect (modo ASYNC) quando o <select>
 * possuir o atributo [field-id] ou [field-search]. Sem esses atributos,
 * o campo continua funcionando normalmente com as <option> já
 * renderizadas pelo PHP (modo local, sem requisição).
 */
function buildAsyncLoader(el)
{
  const fieldId     = el.getAttribute('field-id');
  const fieldSearch = el.getAttribute('field-search');

  if (!fieldId && !fieldSearch) return null;

  return function (query, callback)
  {
    const params = new URLSearchParams();
    params.set('search', query);
    if (fieldId)     params.set('field-id', fieldId);
    if (fieldSearch) params.set('field-search', fieldSearch);

    fetch(`${SEARCHABLE_FIELDS_API_URL}?${params.toString()}`)
      .then((response) => response.json())
      .then((json) => callback(Array.isArray(json) ? json : []))
      .catch(() => callback());
  };
}

/**
 * Monta a opção `create` do TomSelect. Quando o campo tiver [field-search]
 * (formato 2), tenta criar o registro de verdade no servidor via POST
 * (usa a query 'insert' de $GLOBALS['searchable-fields'][key], se houver).
 * Se o servidor não tiver essa query configurada (ou a requisição falhar),
 * cai para o comportamento padrão do TomSelect (cria só localmente).
 * Para [field-id] (tb_cruds_fields, sem query de inserção própria) ou
 * campos sem nenhum dos dois atributos, mantém o boolean de sempre.
 */
function buildCreateHandler(el, allowCreate)
{
  if (!allowCreate) return false;

  const fieldSearch = el.getAttribute('field-search');
  if (!fieldSearch) return true;

  return function (input, callback)
  {
    fetch(SEARCHABLE_FIELDS_API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 'field-search': fieldSearch, value: input }),
    })
      .then((response) => response.json())
      .then((json) => {
        if (json && json.value !== undefined && json.value !== null) {
          callback(json);
        } else {
          // Sem query de inserção configurada (ou falha): cria só localmente,
          // igual ao comportamento padrão do TomSelect.
          callback({ value: input, display: input });
        }
      })
      .catch(() => callback({ value: input, display: input }));
  };
}

document.addEventListener('DOMContentLoaded', () =>
{
  //1) Busca normal
  document.querySelectorAll('select[data-search]').forEach((el) =>
  {
    if (el.tomselect) return;

    const allowCreate = el.hasAttribute('data-allow-create');
    const load = buildAsyncLoader(el);

    new TomSelect(el, {
      create: buildCreateHandler(el, allowCreate),
      plugins: ['dropdown_input'],
      // 'value'/'display' é o mesmo formato retornado pela API (ASYNC)
      // e pelas <option> já renderizadas, então os dois modos convivem.
      valueField: 'value',
      labelField: 'display',
      searchField: ['display'],
      ...(load ? { load, preload: false } : {}),
    });
  });

  // 2) Múltiplos
  document.querySelectorAll('select[data-search-multiple]').forEach((el) =>
  {
    if (el.tomselect) return;

    const min = parseInt(el.dataset.min || 0, 10);
    const max = parseInt(el.dataset.max || 0, 10);
    const allowCreate = el.hasAttribute('data-allow-create');
    const load = buildAsyncLoader(el);

    const ts = new TomSelect(el, {
      create: buildCreateHandler(el, allowCreate),
      maxItems: max || null,
      plugins: ['remove_button', 'dropdown_input'],
      valueField: 'value',
      labelField: 'display',
      searchField: ['display'],
      ...(load ? { load, preload: false } : {}),
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
